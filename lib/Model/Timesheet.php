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

		$this->addExpression('duration')->set(function($m,$q){
			return $q->expr("(TIMESTAMPDIFF(SECOND,[0], IFNULL([1],'[2]')))",[$m->getElement('starttime'),$m->getElement('endtime'),$this->app->now]);
		});

		// $this->addExpression('project')->set(function($m,$q){
		// 	$m->
		// });

	}
}