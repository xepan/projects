<?php

namespace xepan\projects;

class Widget_DepartmentTaskPerformance extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->report->enableFilterEntity('date_range');
	}

	function recursiveRender(){
		$department_employees = $this->add('xepan\hr\Model_Employee');
		$department_employees->addCondition('department_id',$this->app->employee['department_id']);
		$department_employees->addCondition('status','Active');

		$employee = [];
		foreach ($department_employees as $emp){
			$employee [] = $emp->id;
		}
		
		$task_performance = $this->add('xepan\projects\Model_Widget_TaskPerformance',['employee'=>$employee,'start_date'=>$this->report->start_date,'end_date'=>$this->app->nextDate($this->report->end_date)]);	
 		
 		$view_info = $this->add('xepan\projects\View_infoBoxes');
 		$view_info->setModel($task_performance);

		return parent::recursiveRender();
	}
}