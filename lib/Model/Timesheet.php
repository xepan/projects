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
		$this->addField('remark')->type('text');

		$this->setOrder('starttime','desc');

		$this->addExpression('start_date')->set('DATE(starttime)');
		$this->addExpression('end_date')->set('DATE(endtime)');

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

		$this->addExpression('score')->set(function($m,$q){
			return $m->add('xepan\base\Model_PointSystem')
					->addCondition('timesheet_id',$q->getField('id'))
					->sum('score');
		});

	}


	function IcanEdit(){
		if(($allow_editing_timesheet_in_days=$this->recall('allow_editing_timesheet_in_days',null)) === null){
			$config_m = $this->add('xepan\projects\Model_Config_ReminderAndTask');
			$config_m->tryLoadAny();
			$this->memorize('allow_editing_timesheet_in_days',$config_m['allow_editing_timesheet_in_days']);
			$allow_editing_timesheet_in_days = $config_m['allow_editing_timesheet_in_days'];
		}
		if(!$allow_editing_timesheet_in_days) {
			$allow_editing_timesheet_in_days=0;
		}
		
		$allow_editing_timesheet_in_days--;

		return (strtotime($this['endtime']) >= strtotime($this->app->today.(-1*$allow_editing_timesheet_in_days )." day"));

	}

	function appliedRules(){
		$applied_rules=array_merge([0],explode(",",$this->ref('task_id')->get('applied_rules')));

		return $this->add('xepan\projects\Model_Task')
					->addCondition('id','in',$applied_rules);
	}
}