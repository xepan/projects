<?php

namespace xepan\projects;

class page_taskscalendar extends \xepan\base\Page {
	function init(){
		parent::init();

		$employee_field = $this->app->stickyGET('employee_field');
		$date_field = $this->app->stickyGET('date_field');
		$employee_only = $this->app->stickyGET('employee_only');
		$type_of_tasks = $this->app->stickyGET('type_of_tasks');
		
		$m = $this->add('xepan\projects\Model_Task');
		$m->setOrder('starting_date','desc');
		if($employee_only)
			$m->addCondition('assign_to_id',$employee_only);
		if($type_of_tasks)
			$m->addCondition('type',$type_of_tasks);
		$v = $this->add('xepan\projects\View_TaskCalendar',['employee_field_to_set'=>"#".$employee_field,'startingdate_field_to_set'=>"#".$date_field,'employee_only'=>$employee_only]);
		$v->setModel($m);
	}
}