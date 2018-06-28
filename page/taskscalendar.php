<?php

namespace xepan\projects;

class page_taskscalendar extends \xepan\base\Page {
	function init(){
		parent::init();

		$employee_field = $this->app->stickyGET('employee_field');
		$date_field = $this->app->stickyGET('date_field');
		
		$m = $this->add('xepan\projects\Model_Task');
		$m->setOrder('starting_date','desc');
		$v = $this->add('xepan\projects\View_TaskCalendar',['employee_field_to_set'=>"#".$employee_field,'startingdate_field_to_set'=>"#".$date_field]);
		$v->setModel($m);
	}
}