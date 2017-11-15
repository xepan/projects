<?php

namespace xepan\projects;

class Widget_MyTaskStatus extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->chart = $this->add('xepan\base\View_Chart');
	}

	function recursiveRender(){
		$employee_task_status = $this->add('xepan\projects\Model_Widget_EmployeeTaskStatus',['entity'=>'Personal']);

		$this->chart->setType('bar')
     		        ->setModel($employee_task_status,'name',['total_tasks','total_pending_tasks','total_hours_alloted','total_estimated_hours','total_hours_taken'])
     		        ->setGroup(['total_tasks','total_pending_tasks','total_hours_alloted','total_estimated_hours','total_hours_taken'])
     		        ->setTitle('Employee Task Status')
     		        ->rotateAxis();

		return parent::recursiveRender();
	}
}