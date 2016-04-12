<?php

namespace xepan\projects;

class Model_Employee extends \xepan\hr\Model_Employee{
	function init(){
		parent::init();

		$this->addExpression('team_members')->set("'Todo'");
		$this->addExpression('project_name')->set("'Todo'");
		$this->addExpression('total_task')->set("'Todo'");
		$this->addExpression('completed_task')->set("'Todo'");
		$this->addExpression('pending_task')->set("'Todo'");
	}
}