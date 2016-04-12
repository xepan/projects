<?php

namespace xepan\projects;

class Model_Timesheet extends \xepan\base\Model_Table{
	public $table = "timesheet";
	function init(){
		parent::init();

		$this->hasOne('xepan\projects\Task', 'task_id');
		$this->hasOne('xepan\hr\Employee', 'employee_id');
		$this->addField('starttime')->type('datetime');
		$this->addField('endtime')->type('datetime');
		$this->addField('remark');
	}
}