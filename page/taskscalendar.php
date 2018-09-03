<?php

namespace xepan\projects;

class page_taskscalendar extends \xepan\base\Page {
	function init(){
		parent::init();

		$employee_field = $this->app->stickyGET('employee_field');
		$date_field = $this->app->stickyGET('date_field');
		$type_of_tasks = $this->app->stickyGET('type_of_tasks');
		$follow_type_field = $this->app->stickyGET('follow_type_field');
		
		
		$m = $this->add('xepan\projects\Model_Task');
		$m->setOrder('starting_date','desc');
		if($type_of_tasks)
			$m->addCondition('type',$type_of_tasks);
		$v = $this->add('xepan\projects\View_TaskCalendar',[
				'employee_field_to_set'=>"#".$employee_field,
				'startingdate_field_to_set'=>"#".$date_field,
				'follow_type_field_to_set'=>"#".$follow_type_field,
				'defaultView'=>'month','title_field'=>'assign_to','add_employee_filter'=>true,'default_task_type'=>'Followup'
			]);
		$v->setModel($m);
	}
}