<?php

namespace xepan\projects;

class Widget_PostTaskPerformance extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->report->enableFilterEntity('date_range');
	}

	function recursiveRender(){
		$post_employees = $this->add('xepan\hr\Model_Employee');
		$post_employees->addCondition('status','Active');
		$post_employees->addCondition('post_id',$this->app->employee['post_id']);

		$employee = [];
		foreach ($post_employees as $emp){
			$employee [] = $emp->id;
		}
		
		$task_performance = $this->add('xepan\projects\Model_Widget_TaskPerformance',['employee'=>$employee,'start_date'=>$this->report->start_date,'end_date'=>$this->app->nextDate($this->report->end_date)]);	
 		
 		$view_info = $this->add('xepan\projects\View_infoBoxes');
 		$view_info->setModel($task_performance);

		return parent::recursiveRender();
	}
}