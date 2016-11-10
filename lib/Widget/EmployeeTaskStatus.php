<?php

namespace xepan\projects;

class Widget_EmployeeTaskStatus extends \View{
	function init(){
		parent::init();
	}

	function recursiveRender(){
		// $this->model,['name','total_tasks','total_pending_tasks','total_hours_alloted','total_estimated_hours','total_minutes_taken','total_hours_taken']);
		$this->add('xepan\base\View_Chart')
     		->setType('bar')
     		->setModel($this->model,'name',['total_tasks','total_pending_tasks','total_hours_alloted','total_estimated_hours','total_hours_taken'])
     		->setGroup(['total_tasks','total_pending_tasks','total_hours_alloted','total_estimated_hours','total_hours_taken'])
     		->setTitle('Employee Task Status')
     		->addClass('col-md-8')
     		->rotateAxis();
		parent::recursiveRender();
	}
}