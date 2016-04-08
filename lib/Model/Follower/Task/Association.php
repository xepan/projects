<?php

namespace xepan\projects;

class Model_Follower_Task_Association extends \xepan\base\Model_Table{
	public $table = "follower_task_association";
	function init(){
		parent::init();

		$this->hasOne('xepan\projects\Task','task_id');
		$this->hasOne('xepan\hr\Employee','employee_id');
	}
}