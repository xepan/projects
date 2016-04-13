<?php

namespace xepan\projects;

class View_TaskList extends \xepan\base\Grid{

	// public $show_completed=true;
	public $running_task_id = null;

	function init(){
		parent::init();

		$sub_tasks=$this->add('xepan\projects\Model_Formatted_Task',['name'=>'task_'.$this->model->id]);
		
		if($this->filter =='Completed')
			$sub_tasks->addCondition('status','Completed');

		if($this->filter =='Pending')
			$sub_tasks->addCondition('status','Pending');

		if(!$this->mytask)			
			$sub_tasks->addCondition('employee_id',$this->app->employee->id);

		if($sub_tasks->count()->getOne() > 0){
			$sub_v =$this->add('xepan\projects\View_TaskList',['running_task_id'=>$this->running_task_id],'sub_tasks',['view/tasklist1','nested_template']);
			$sub_v->setModel($sub_tasks);
			$sub_v->add('xepan\hr\Controller_ACL',['action_btn_group'=>'xs']);
			$sub_v->add('xepan\base\Controller_Avatar',['options'=>['size'=>20],'name_field'=>'employee','default_value'=>'']);
			$this->current_row_html['sub_tasks']= $sub_v->getHTML();
		}else{
			$this->current_row_html['sub_tasks']= "";
		}

	}
		
	
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
				
		return parent::formatRow();
	}
	function defaultTemplate(){
		return['view/tasklist1'];
	}
}