<?php

namespace xepan\projects;

class View_TaskList extends \xepan\base\Grid{
	// public $show_completed=true;
	public $view_reload_url=null;
	public $running_task_id = null;

	function init(){
		parent::init();

		$this->js('reload')->reload();

		$this->running_task_id = $this->add('xepan\projects\Model_Employee')
	    					->load($this->app->employee->id)
	    					->get('running_task_id');

		// $this->view_reload_url = $this->app->url(null,['cut_object'=>$this->getView()->name]);
		$this->view_reload_url = $this->app->url(null,['cut_object'=>$this->name]);
	    $this->js(true)->_load('timer.jquery');

	/***************************************************************************
	  Timesheet PLAY/STOP
	***************************************************************************/
	$this->on('click','.current_task_btn',function($js,$data){
			
			$this->endAnyTaskIfRunning();	

			$stop_js =  $this->stopAll($js);
			$run_current_js = [];
			if($data['action']=='start'){ // needs to start now
				$run_current_js =  $this->runTask($data,$js);
			}

			return array_merge($stop_js, $run_current_js);
			
		});


	}
	
	function formatRow(){
		if($this->model['created_by_id'] != $this->app->employee->id){
			$this->current_row_html['trash'] = '';
		}

		$this->current_row['task_no']= str_pad($this->model->id, 4, '0', STR_PAD_LEFT);
		if($this->isCurrentTask()){
			$this->createRunning();
		}else{
			$this->createStopped();
		}


		$action_btn_list = [];
		
		//My Task - self assign
		if($this->model['created_by_id'] == $this->app->employee->id && ($this->model['assign_to_id'] == null || $this->model['assign_to_id'] == $this->model['created_by_id'])){
			$action_btn_list = $this->model->self_assign_actions[$this->model['status']]?:[];

		}elseif(($this->model['assign_to_id'] == $this->app->employee->id)){
			// Assign To Me
			$action_btn_list = $this->model->assign_to_me_actions[$this->model['status']]?:[];

		}elseif(($this->model['created_by_id'] == $this->app->employee->id) && ($this->model['assign_to_id']!= $this->app->employee->id)){
			// Assign By Me
			$action_btn_list = $this->model->assign_by_me_actions[$this->model['status']]?:[];
		}
			
		if(!isset($this->current_row_html['action'])){
			$action_btn = $this->add('AbstractController')->add('xepan\hr\View_ActionBtn',['actions'=>$action_btn_list,'id'=>$this->model->id,'status'=>$this->model['status'],'action_btn_group'=>null]);
			$this->current_row_html['action'] = $action_btn->getHTML();
			$action_btn_list = [];
		}
		
		return parent::formatRow();
	}

	function setModel($model,$fields=null){
		$m= parent::setModel($model,$fields);
		$this->on('click','.acl-action',[$this,'manageAction']);
		
		return $m;
	}

	function manageAction($js,$data){	

		$this->app->inAction=true;

		$this->model = $this->model->newInstance()->load($data['id']?:$this->api->stickyGET($this->name.'_id'));
		$action=$data['action']?:$this->api->stickyGET($this->name.'_action');
		if($this->model->hasMethod('page_'.$action)){
			$p = $this->add('VirtualPage');
			$p->set(function($p)use($action){
				try{
					$this->api->db->beginTransaction();
						$page_action_result = $this->model->{"page_".$action}($p);						
					if($this->app->db->intransaction()) $this->api->db->commit();
				}catch(\Exception_StopInit $e){

				}catch(\Exception $e){
					if($this->app->db->intransaction()) $this->api->db->rollback();
					throw $e;
				}
				if(isset($page_action_result) or isset($this->app->page_action_result)){
					
					if(isset($this->app->page_action_result)){						
						$page_action_result = $this->app->page_action_result;
					}

					$js=[];
					if($page_action_result instanceof \jQuery_Chain) {
						$js[] = $page_action_result;
					}
					$js[]=$this->getView()->js()->univ()->closeDialog();
					$js[]= $this->getView()->js()->reload(null,null,$this->view_reload_url);
					
					$this->getView()->js(null,$js)->execute();
					// $p->js(true)->univ()->location();
				}
			});
			return $js->univ()->frameURL('Action',$this->api->url($p->getURL(),[$this->name.'_id'=>$data['id'],$this->name.'_action'=>$data['action']]));
		}elseif($this->model->hasMethod($action)){
			try{
					$this->api->db->beginTransaction();
					$this->model->$action();					
					$this->api->db->commit();
				}catch(\Exception_StopInit $e){

				}catch(\Exception $e){					
					$this->api->db->rollback();
					throw $e;
				}
			$this->getView()->js()->reload(null,null,$this->view_reload_url)->execute();
			// $this->getView()->js()->univ()->location()->execute();
		}else{
			return $js->univ()->errorMessage('Action "'.$action.'" not defined in Model');
		}
	}

	function getView(){
		return $this;
	}

	function isCurrentTask(){
		return $this->running_task_id == $this->model->id;
	}

	function createRunning(){
		$this->current_row['icon'] = 'fa-stop';
		$this->current_row['event_action'] = 'stop';
		$this->current_row['running_class'] = '';

		if($this->model['is_started'] && $this->model['is_running']){
			$this->current_row['running-task']='text-danger';
		}

		$timesheet = $this->add('xepan\projects\Model_Timesheet')
						  ->addCondition('employee_id',$this->app->employee->id) 	
						  ->addCondition('task_id',$this->model->id) 	
						  ->addCondition('endtime',null)
						  ->tryLoadAny();
						  			       
		$this->js(true)->_selector('#'.$this->name.' .current_task_btn[data-id='.$this->model->id.'] .duration')->timer(['seconds'=>$timesheet['duration']]);
	}

	function createStopped(){
		$this->current_row['icon'] = 'fa-play';
		$this->current_row['event_action'] = 'start';
		$this->current_row['running_class'] = '';

		if(!$this->model['is_started'] || !$this->model['is_running']){
			$this->current_row['running-task']='';
		}
	}

	function runTask($data, $js){
		$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
					
		$model_timesheet['task_id'] = $data['id'];
		$model_timesheet['employee_id'] = $this->app->employee->id;
		$model_timesheet['starttime'] = $this->app->now;
		$model_timesheet->save();

		return [
				$this->js()->_selector('.current_task_btn[data-id='.$data['id'].']')->removeClass('fa-play')->addClass('fa-stop'),
				$this->js()->_selector('.current_task_btn[data-id='.$data['id'].'] .duration')->timer(['seconds'=>$model_timesheet['duration']]),
				$this->js()->_selector('.xepan-mini-task')->trigger('reload'),
				$js->data('action','stop'), // next possible_action

			];
	}

	function stopAll($js){

		return [	
					$this->js()->_selector('.current_task_btn')->removeClass('fa-stop')->addClass('fa-play'),
					$this->js()->_selector('.current_task_btn .duration')->timer('remove'),
					$this->js()->_selector('.xepan-mini-task')->trigger('reload'),
					$js->data('action','start') // next possible_action
				];
	}

	function endAnyTaskIfRunning(){
		$model_close_timesheet = $this->add('xepan\projects\Model_Timesheet');

		$model_close_timesheet->addCondition('employee_id',$this->app->employee->id);
		$model_close_timesheet->addCondition('endtime',null);
		$model_close_timesheet->tryLoadAny();

		if($model_close_timesheet->loaded()){
			if(!$model_close_timesheet['endtime']){
				$model_close_timesheet['endtime'] = $this->app->now;
				$model_close_timesheet->saveAndUnload();
			}
		}
	}


	function defaultTemplate(){
		return['view/tasklist1'];
	}
}