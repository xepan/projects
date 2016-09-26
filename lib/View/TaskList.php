<?php

namespace xepan\projects;

class View_TaskList extends \xepan\base\Grid{

	// public $show_completed=true;
	public $running_task_id = null;

	function init(){
		parent::init();

		$this->running_task_id = $this->add('xepan\projects\Model_Employee')
	    					->load($this->app->employee->id)
	    					->get('running_task_id');


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

		$this->current_row['task_no']= str_pad($this->model->id, 4, '0', STR_PAD_LEFT);
		if($this->isCurrentTask()){
			$this->createRunning();
		}else{
			$this->createStopped();
		}

		return parent::formatRow();
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