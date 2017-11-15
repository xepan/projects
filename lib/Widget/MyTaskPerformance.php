<?php

namespace xepan\projects;

class Widget_MyTaskPerformance extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->report->enableFilterEntity('date_range');
	}

	function recursiveRender(){
		$employee = [];
		$employee = $this->app->employee->id;
		
		$task_performance = $this->add('xepan\projects\Model_Widget_TaskPerformance',['employee'=>$employee,'start_date'=>$this->report->start_date,'end_date'=>$this->app->nextDate($this->report->end_date)]);	
 		
 		$view_info = $this->add('xepan\projects\View_infoBoxes');
 		$view_info->setModel($task_performance);

		return parent::recursiveRender();
	}
}