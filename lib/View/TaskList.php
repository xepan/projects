<?php

namespace xepan\projects;

class View_TaskList extends \xepan\base\Grid{
	// public $show_completed=true;
	public $view_reload_url=null;
	public $running_task_id = null;
	public $play_wrapper_template=null;
	public $del_action_wrapper;
	
	function init(){
		parent::init();
		
		
		$this->js('reload')->reload();
		$this->running_task_id = $this->add('xepan\projects\Model_Employee')
	    					->load($this->app->employee->id)
	    					->get('running_task_id');

	    /***************************************************************************
			Virtual page for TASK DETAIL
		***************************************************************************/
		$self = $this;
		$self_url = $this->app->url(null,['cut_object'=>$this->name]);	

	    $this->js('click')->_selector("#".$this->getJSID().' .task-item')->univ()->frameURL('TASK/REQUEST DETAIL',[$this->app->url('xepan_projects_taskdetail'),'task_id'=>$this->js()->_selectorThis()->data('id')]);
		$this->view_reload_url = $this->app->url(null,['cut_object'=>$this->name]);
	    $this->js(true)->_load('timer.jquery');

		/***************************************************************************
		  Timesheet PLAY/STOP
		***************************************************************************/
		$vp_describe = $this->add('VirtualPage');
		$vp_describe->set(function($p){
			$data = $this->app->stickyGET('data');
			$data=json_decode($data,true);

			$my_running_task = $this->getMyRunningTask();

			$msg ="";
			if($data['action']=='start'){
				$new_task = $this->add('xepan\projects\Model_Task')->load($data['id']);
				$msg = 'Starting "<strong>'.$new_task['task_name'].'</strong>" and <br/>';
			}

			$msg .= 'Stopping "<strong>'.$my_running_task['task_name'].'</strong>" but this task requires you to fill about your ended task slot';
			$p->add('View')->addClass('alert alert-info')->setHTML($msg);

			$ro_array=[];
			if($my_running_task['manage_points']){
				foreach (explode(",",$my_running_task['applied_rules']) as $rule_id) {
					if(!$rule_id) continue;
					$rule = $this->add('xepan\base\Model_Rules')->tryLoad($rule_id);
					if(!$rule->loaded()) continue;
					foreach ($rule->ref('xepan\base\RulesOption') as $ro) {
						$ro_array[$ro->id]=$ro['name'];
						// $form->addField('Readonly','rule_o_name_'.$ro->id,$ro['name']);
						// $form->addField('Number','rule_o_qty_'.$ro->id,'Work Done');
					}
				}
				// $form->add('View')->addClass('alert alert-danger')->set('TODO');
			}

			if($my_running_task['describe_on_end']){
				$form_layout_array = ['what_you_did~What Exactly Did You Do In This Time'=>'Work Report~c1~12'];
			}else{
				$form_layout_array = [];
			}

			$i=2;
			foreach ($ro_array as $id => $title) {
				$form_layout_array['rule_o_name_'.$id.'~'.($i-1).': '.$title] = ($i==2?'Rules Qty~':'').'c'.$i.'~6';
				$form_layout_array['rule_qty_'.$id.'~'] = 'cq'.$i.'~1';
				$form_layout_array['rule_remark_'.$id.'~'] = 'cr'.$i.'~4';
				$form_layout_array['rule_btn_'.$id.'~'] = 'hs'.$i.'~1';
				$form_layout_array['empty'.$id.'~'] = 'br'.$i.'~12';
				$i++;
			}
			
			$form = $p->add('Form');
			$form->add('xepan\base\Controller_FLC')
				->showLables(true)
				->addContentSpot()
				->makePanelsCoppalsible(true)
				->layout($form_layout_array);

			if($my_running_task['describe_on_end']){
				$form->addField('Text','what_you_did');
			}


			foreach ($ro_array as $id => $title) {
				$form->addField('Readonly','rule_o_name_'.$id,$title);
				$form->addField('Line','rule_qty_'.$id);
				$form->addField('Line','rule_remark_'.$id);
				$form->layout->add('View',null,'rule_btn_'.$id)->setHtml('<a href="#" class="history" data-rule_opt_id="'.$id.'">History</a>');
			}

			$rule_history_vp = $p->add('VirtualPage');
			$rule_history_vp->set(function($p){
				$rule_opt_id = $this->app->stickyGET('rule_opt_id');
				$points = $this->add('xepan\base\Model_PointSystem');
				$points->addCondition('rule_option_id',$rule_opt_id);
				$points->addCondition('created_by_id',$this->app->employee->id);
				$points->setOrder('created_at','desc');
				$g=$p->add('Grid');
				$g->setModel($points,['created_at','timesheet_id','qty','score','remarks']);
				$g->addPaginator(100);
			});

			$form->js('click')->_selector('.history')->univ()->frameURL('Rule History',[$rule_history_vp->getURL(),'rule_opt_id'=>$this->js()->_selectorThis()->data('rule_opt_id')]);
			$form->addSubmit('Stop And Proceed');

			if($form->isSubmitted()){
				try{
					$this->app->db->beginTransaction();
					$my_running_tasksheet = $this->getMyRunningTimeSheet();
					$my_running_tasksheet_id = $my_running_tasksheet->id;
					$my_running_tasksheet['remark'] = $form['what_you_did'];
					$my_running_tasksheet['endtime'] = $this->app->now;
					$my_running_tasksheet->saveAndUnload();

					$my_running_task['status'] = 'Pending';
					$my_running_task->save();

					$js = $form->js();
					$stop_js =  $this->stopAll($js);
					$run_current_js = [];
					if($data['action']=='start'){ // needs to start now
						$run_current_js =  $this->runTask($data,$js);
					}

					$run_current_js[] = $this->js()->closest('.xepan-tasklist-grid')->trigger('reload');

					foreach ($ro_array as $id => $title) {
						if(!$form['rule_qty_'.$id]) continue;
						if($form['rule_qty_'.$id] && !is_numeric($form['rule_qty_'.$id])) $form->displayError('rule_qty_'.$id,'This value must be a integer');
						
						$ro = $this->add('xepan\base\Model_RulesOption');
						$ro->load($id);
						$ps = $this->add('xepan\base\Model_PointSystem');
						$ps['rule_id'] = $ro['rule_id'];
						$ps['rule_option_id'] = $id;
						$ps['timesheet_id'] = $my_running_tasksheet_id;
						$ps['remarks'] = $form['rule_remark_'.$id];
						$ps['contact_id'] = $this->app->employee->id;
						$ps['qty'] = $form['rule_qty_'.$id];
						// $ps['score'] = $ro['score_per_qty'] * $form['rule_qty_'.$id];
						$ps->save();
					}	
					
					$this->app->db->commit();
					$form->js(null,array_merge($stop_js, $run_current_js))->univ()->closeDialog()->execute();
				}catch(\Execption $e){
					$this->app->db->rollback();
					throw $e;
				}

				// throw new \Exception($my_running_task['task_name'], 1);
				
			}

		});

		$grid_id = $this->getJSID();
		$this->on('click','.current_task_btn',function($js,$data)use($vp_describe) {
				
				$my_running_task = $this->getMyRunningTask();

				if($my_running_task && ($my_running_task['describe_on_end'] || $my_running_task['manage_points'])){
					return $js->univ()->frameURL($this->app->url($vp_describe->getURL(),['data'=>json_encode($data)]));
				}

				$this->endAnyTaskIfRunning();	

				$stop_js =  $this->stopAll($js);
				$run_current_js = [];
				if($data['action']=='start'){ // needs to start now
					$run_current_js =  $this->runTask($data,$js);
				}

				$run_current_js[] = $this->js()->closest('.xepan-tasklist-grid')->trigger('reload');
				return array_merge($stop_js, $run_current_js);
				
			});
		}
	
	function formatRow(){
		$thisTask = $this->model;

		$this->current_row['task_no']= str_pad($this->model->id, 4, '0', STR_PAD_LEFT);
		
		if($thisTask['type'] == 'Followup'){
			$this->current_row['dummy_spot'] = ' ';
		}else{
			$this->current_row['contact_info_wrapper'] = ' ';
		}

		if(($thisTask['set_reminder'] AND !$thisTask['is_reminded']) OR ($thisTask['snooze_duration'] != null AND $thisTask['snooze_duration'] >= 0)){
			$this->current_row_html['alarm_wrapper'] = '<i class="fa fa-bell"></i>';
		}else{			
			$this->current_row_html['alarm_wrapper'] = ' ';
		}

		if($thisTask['is_recurring']){
			$this->current_row_html['recurring_wrapper']='<i class="fa fa-repeat"></i>';
		}else{
			$this->current_row_html['recurring_wrapper']=' ';
		}

		if($this->del_action_wrapper){
			$this->current_row['dashboard_sport_for_action']= $thisTask['status'];
			$this->current_row['action_wrapper']= ' ';
		}else{
			$this->current_row['dashboard_sport_for_action']= ' ';
			$this->current_row['status_wrapper']= ' ';
		}		

		if($this->isCurrentTask()){
			$this->createRunning();
		}else{
			$this->createStopped();
		}


		$action_btn_list = $this->model->actions[$this->model['status']];
		// if($this['status'] =='Submitted' AND $this['created_by_employee_status'] == "InActive") {
		// 	var_dump($action_btn_list);
		// 	unset($action_btn_list[0]); // submit
		// 	unset($action_btn_list[2]); // stop recurrence
		// 	unset($action_btn_list[3]); // reset dadeline
		// 	$this->current_row_html['action'] = $action_btn_list;
		// }	

		if($this['assign_employee_status'] == "InActive"){
			unset($action_btn_list[0]); // received
			unset($action_btn_list[3]); // reset dadeline
			$this->current_row_html['action'] = $action_btn_list;
		}else{
			// first Column
			if($thisTask->isMyTask()){
				if($this['status'] =='Pending' && !$thisTask->createdByMe() && $thisTask['type'] !='Followup')
					$action_btn_list = array_diff( $action_btn_list, ['mark_complete'] ); // unset($action_btn_list[1]); // submit
				if($thisTask['type'] == 'Followup')
					$action_btn_list = array_diff( $action_btn_list, ['submit'] );;//unset($action_btn_list[0]); // submit

				if(!$thisTask->createdByMe() && $this['status'] =='Submitted'){
					if($this['created_by_employee_status'] == "InActive"){
						$action_btn_list = array_diff( $action_btn_list, ['mark_complete'] );;//unset($action_btn_list[1]);
						$action_btn_list = array_diff( $action_btn_list, ['reopen'] ); //unset($action_btn_list[2]); 
						
					}else{
						$action_btn_list=[];
						
					}

				}

				if($this['status'] =='Inprogress' && !$thisTask->createdByMe())
					$action_btn_list = array_diff( $action_btn_list, ['mark_submit'] ); //unset($action_btn_list[1]); // mark_submit

				if($this['status'] =='Inprogress' && $thisTask->createdByMe())
					$action_btn_list = array_diff( $action_btn_list, ['mark_submit'] ); //unset($action_btn_list[0]); // mark_submit
			}

			// Second Column

			if($thisTask->IhaveAssignedToOthers() && ($this['status'] == "Inprogress" OR $this['status'] == "Completed")) 
				$action_btn_list = [];

			if($thisTask->IhaveAssignedToOthers() && ($this['status'] == "Pending" OR $this['status'] == "Assigned")){
				unset($action_btn_list[0]);
				unset($action_btn_list[1]);
				unset($action_btn_list[2]);
			} 


			if(!$thisTask->canDelete()){
				$this->current_row_html['delete'] = ' ';
			}

			if(!$thisTask->iCanPlay()){
				$this->current_row['display_play_pause'] = 'none';
			}
			else{
				$this->current_row['display_play_pause'] = 'block';
			}	
		}
			
		// if($thisTask instanceof Model_Followup){
		// 	print_r($this->model->actions);
		// 	echo $this->model['status'];
		// 	die();
		// }

		$action_btn = $this->add('AbstractController')->add('xepan\hr\View_ActionBtn',['actions'=>$action_btn_list?:[],'id'=>$this->model->id,'status'=>$this->model['status'],'action_btn_group'=>'xs']);
		$this->current_row_html['action'] = $action_btn->getHTML();
		
		$this->current_row_html['starting_date'] = date_format(date_create($this['starting_date']), 'jS F Y g:ia');
		$this->current_row_html['deadline'] = date_format(date_create($this['deadline']), 'jS F Y g:ia');
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
					$page_action_result = $this->model->$action();
					$this->api->db->commit();
				}catch(\Exception_StopInit $e){

				}catch(\Exception $e){					
					$this->api->db->rollback();
					throw $e;
				}

				$js=[];
				if(isset($page_action_result) or isset($this->app->page_action_result)){
					
					if(isset($this->app->page_action_result)){						
						$page_action_result = $this->app->page_action_result;
					}

					if($page_action_result instanceof \jQuery_Chain) {
						$js[] = $page_action_result;
					}
					$this->getView()->js(null,$js)->reload(null,null,$this->view_reload_url)->execute();
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

		$task = $this->add('xepan\projects\Model_Task')->load($data['id']);
		$task['status'] = 'Inprogress';
		$task->save();

		return [
				$this->js()->_selector('.current_task_btn[data-id='.$data['id'].']')->removeClass('fa-play')->addClass('fa-stop'),
				$this->js()->_selector('.current_task_btn[data-id='.$data['id'].'] .duration')->timer(['seconds'=>$model_timesheet['duration']]),
				$this->js()->_selector('.xepan-mini-task')->trigger('reload'),
				$js->data('action','stop') // next possible_action

			];
	}

	function stopAll($js){

		return [	
					$this->js()->_selector('.current_task_btn')->removeClass('fa-stop')->addClass('fa-play'),
					$this->js()->_selector('.current_task_btn .duration')->timer('remove'),
					$this->js()->_selector('.xepan-mini-task')->trigger('reload'),
					$this->js()->_selector('.task-assigned-to-me')->trigger('reload'),
					$js->data('action','start') // next possible_action
				];
	}

	function getMyRunningTimeSheet(){
		$model_close_timesheet = $this->add('xepan\projects\Model_Timesheet');

		$model_close_timesheet->addCondition('employee_id',$this->app->employee->id);
		$model_close_timesheet->addCondition('endtime',null);
		$model_close_timesheet->tryLoadAny();
		
		if($model_close_timesheet->loaded()) return $model_close_timesheet;
		return false;
	}

	function getMyRunningTask(){
		$model_close_timesheet = $this->getMyRunningTimeSheet();

		if($model_close_timesheet) {
			$task = $this->add('xepan\projects\Model_Task')->load($model_close_timesheet['task_id']);
			return $task;
		}
		return false;
	}

	function endAnyTaskIfRunning(){

		if($model_close_timesheet = $this->getMyRunningTimeSheet()){
			$task = $this->add('xepan\projects\Model_Task')->load($model_close_timesheet['task_id']);
			$task['status'] = 'Pending';
			$task->save();

			if(!$model_close_timesheet['endtime']){
				$model_close_timesheet['endtime'] = $this->app->now;
				$model_close_timesheet->saveAndUnload();
			}
		}
	}

	function render(){
		$this->js(true)->_selector('#'.$this->getJSID().' [title]')->tooltip();
		parent::render();
	}

	function defaultTemplate(){
		return['view/tasklist1'];
	}
}