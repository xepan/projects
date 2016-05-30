<?php

namespace xepan\projects;

class View_TaskList extends \xepan\base\Grid{

	// public $show_completed=true;
	public $running_task_id = null;
	
	function formatRow(){

		$this->current_row['task_no']= str_pad($this->model->id, 4, '0', STR_PAD_LEFT);
		if($this->running_task_id == $this->model->id){
			$this->current_row['icon'] = 'fa-stop';
			$this->current_row['event_action'] = 'stop';
			$this->current_row['running_class'] = '';

			$timesheet  = $this->add('xepan\projects\Model_Timesheet')->loadBy('task_id',$this->model->id);			
			$this->js(true)->_selector('.fa-stop .duration')->timer(['seconds'=>$timesheet['duration']]);

		}else{
			$this->current_row['icon'] = 'fa-play';
			$this->current_row['event_action'] = 'start';
			$this->current_row['running_class'] = '';
		}

		if($this->model['is_started'] && $this->model['is_running']){
			$this->current_row['running-task']='text-danger';
		}else{
			$this->current_row['running-task']='';
		}

		return parent::formatRow();
	}
	function defaultTemplate(){
		return['view/tasklist1'];
	}
}