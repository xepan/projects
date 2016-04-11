<?php

namespace xepan\projects;

class View_TopView extends \View{
	function init(){
		parent::init();

		$model_task = $this->add('xepan\projects\Model_Task')->addCondition('employee_id',$this->app->employee->id);
		$model_task->addCondition('status','Pending');			
		$pending_task = $model_task->count()->getOne();						
		$this->add('View',null,'pending_task')->set("Pending Tasks: ".$pending_task);		
	}

	function defaultTemplate(){
		return['view\topview'];
	}
}