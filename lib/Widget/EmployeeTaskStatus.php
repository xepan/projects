<?php

namespace xepan\projects;

class Widget_EmployeeTaskStatus extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->chart = $this->add('xepan\base\View_Chart');
		$this->report->enableFilterEntity('Employee');
	}

	function recursiveRender(){
		$employee_id ='';
		if(isset($this->report->employee))
			$employee_id = $this->report->employee;
		
		$employee_task_status = $this->add('xepan\projects\Model_Widget_EmployeeTaskStatus',['entity'=>'Employee','employee_id'=>$employee_id]);

		$this->chart->setType('bar')
     		        ->setModel($employee_task_status,'name',['total_tasks','total_pending_tasks','total_hours_alloted','total_estimated_hours','total_hours_taken'])
     		        ->setGroup(['total_tasks','total_pending_tasks','total_hours_alloted','total_estimated_hours','total_hours_taken'])
     		        ->setTitle('Employee Task Status')
     		        ->rotateAxis();

		return parent::recursiveRender();
	}
}