<?php

namespace xepan\projects;

class Widget_EmployeeTimesheet extends \View{
	function init(){
		parent::init();
	}

	function recursiveRender(){
		$this->add('Grid')->setModel($this->model,['task_name','created_by','starting_date','deadline','estimate_time','time_consumed']);
		parent::recursiveRender();
	}
}