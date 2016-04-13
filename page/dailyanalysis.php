<?php

namespace xepan\projects;

class page_dailyanalysis extends \xepan\projects\page_sidemenu{
	function init(){
		parent::init();

		$employee_id = $this->app->stickyGET('contact_id');
		
		$model_employee = $this->add('xepan\projects\Model_Employee')->load($employee_id);

		$model_employee->addExpression('task')->set('"ToDo"');
		$model_employee->addExpression('projects')->set('"ToDo"');
		$model_employee->addExpression('time_taken')->set('"ToDo"');
		$model_employee->addExpression('breaks')->set('"ToDo"');
	}

	function defaultTemplate(){
		return['view\dailyanalysis'];
	}
}