<?php

namespace xepan\projects;

class Model_Timesheet extends \xepan\base\Model_Table{
	public $table = "timesheet";
	function init(){
		parent::init();

		$this->hasOne('xepan\projects\Task', 'task_id');
		$this->hasOne('xepan\hr\Employee', 'employee_id');
		$this->addField('starttime')->type('datetime')->display(['form'=>'DateTimePicker']);
		$this->addField('endtime')->type('datetime')->display(['form'=>'DateTimePicker']);
		$this->addField('remark');

		$this->setOrder('starttime','desc');

		$this->addExpression('duration')->set(function($m,$q){
			return $q->expr("(TIMESTAMPDIFF(SECOND,[0], IFNULL([1],'[2]')))",[$m->getElement('starttime'),$m->getElement('endtime'),$this->app->now]);
		});

		$this->addExpression('duration_in_hms')->set(function($m,$q){
			return $q->expr("SEC_TO_TIME([0])",[$m->getElement('duration')]);
		})->caption('Duration');

		$this->addExpression('project_id')->set(function($m,$q){
			return $m->refSQL('task_id')->fieldQuery('project_id');
		});

		$this->addExpression('project')->set(function($m,$q){
			return $m->refSQL('task_id')->fieldQuery('project');
		});

	}
}