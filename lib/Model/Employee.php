<?php

namespace xepan\projects;

class Model_Employee extends \xepan\hr\Model_Employee{
	function init(){
		parent::init();
		
		$this->addExpression('running_task')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'running_task'])
						->addCondition('endtime',null)
						->addCondition('employee_id',$q->getField('id'))
						->setOrder('starttime','desc')
						->setLimit(1)
						->fieldQuery('task');
		});

<<<<<<< HEAD
		$this->addExpression('team_members')->set("'Todo'");
		$this->addExpression('project_name')->set("'Todo'");
		$this->addExpression('total_task')->set("'Todo'");
		$this->addExpression('completed_task')->set("'Todo'");
		$this->addExpression('pending_task')->set("'Todo'");
		$this->addExpression('current_task')->set("'Todo'");
		$this->addExpression('task_starting_time')->set("'Todo'");
		$this->addExpression('time_elapsed')->set("'Todo'");
=======
		$this->addExpression('running_task_id')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'running_task'])
						->addCondition('endtime',null)
						->addCondition('employee_id',$q->getField('id'))
						->setOrder('starttime','desc')
						->setLimit(1)
						->fieldQuery('task_id');
		});

		$this->addExpression('running_task_since')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'running_task'])
						->addCondition('endtime',null)
						->addCondition('employee_id',$q->getField('id'))
						->setOrder('starttime','desc')
						->setLimit(1)
						->fieldQuery('duration');
		});

		$this->addExpression('project')->set(function($m,$q){
			$p=$this->add('xepan\projects\Model_Project');
			$task_j = $p->join('task.project_id');
			$task_j->addField('task_id','id');
			$p->addCondition($q->expr('[0]=[1]',[$p->getElement('task_id'),$m->getField('running_task_id')]));
			return $p->fieldQuery('name');
		});

		$this->hasMany('xepan\projects\Task','employee_id');

		$this->addExpression('pending_tasks_count')->set(function ($m,$q){
			return $m->refSQL('xepan\projects\Task')
						->addCondition('status','Pending')
						->count();
		});



		$this->addExpression('performance')->set("'Todo'");
>>>>>>> 0dab4a74bbdd58712b72a97ef36256d95f5dae43
	}
}