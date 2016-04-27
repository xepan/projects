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
			$this->current_row['running_class'] = ' alert alert-info';

		}else{
			$this->current_row['icon'] = 'fa-play';
			$this->current_row['event_action'] = 'start';
			$this->current_row['running_class'] = '';
		}

		// $this->current_row['total_duration']= date('H:i:s',strtotime($this->current_row['total_duration']));
				
		return parent::formatRow();
	}
	function defaultTemplate(){
		return['view/tasklist1'];
	}
}