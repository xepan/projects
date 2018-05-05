<?php

namespace xepan\projects;

class Model_Employee extends \xepan\hr\Model_Employee{

	public $status = ['Active','InActive'];
	public $actions = ['Active'=>['view','manage_regular_tasks'],'InActive'=>['view']];
	public $acl_type = 'Employee_Running_Task_And_Timesheet';

	function init(){
		parent::init();
		
		$this->addExpression('running_task')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'running_task'])
						->addCondition('endtime',null)
						->addCondition('employee_id',$q->getField('id'))
						->setOrder('starttime','desc')
						->setLimit(1)
						->fieldQuery('task');
		})->sortable(true);


		$this->addExpression('running_task_id')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'running_task'])
						->addCondition('endtime',null)
						->addCondition('employee_id',$q->getField('id'))
						->setOrder('starttime','desc')
						->setLimit(1)
						->fieldQuery('task_id');
		})->sortable(true);

		$this->addExpression('running_task_since')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'running_task'])
						->addCondition('endtime',null)
						->addCondition('employee_id',$q->getField('id'))
						->setOrder('starttime','desc')
						->setLimit(1)
						->fieldQuery('duration');
		})->sortable(true);

		$this->addExpression('project')->set(function($m,$q){
			$p=$this->add('xepan\projects\Model_Project');
			$task_j = $p->join('task.project_id');
			$task_j->addField('task_id','id');
			$p->addCondition($q->expr('[0]=[1]',[$p->getElement('task_id'),$m->getField('running_task_id')]));
			return $p->fieldQuery('name');
		})->sortable(true);

		$this->hasMany('xepan\projects\Task','assign_to_id');

		$this->addExpression('pending_tasks_count')->set(function ($m,$q){
			return $m->refSQL('xepan\projects\Task')
						->addCondition('status',['Pending','Submitted','Assigned','Inprogress'])
						->count();
		})->sortable(true);



		$this->addExpression('performance')->set("'Todo'");
	}

	function page_manage_regular_tasks($p){
		$tasks = $this->add('xepan\projects\Model_Task');
		$tasks->addCondition('assign_to_id',$this->id);
		$tasks->addCondition('is_regular_work',true);
		$tasks->addCondition('type','Task');

		$tasks->getElement('applied_rules')->display(['form'=>'xepan\base\NoValidateDropDown']);

		$tasks->getElement('starting_date')->defaultValue($this->app->now);

		$crud = $p->add('xepan\base\CRUD');
		$crud->setModel($tasks,['task_name','description','describe_on_end','applied_rules','manage_points'],['task_name','description','describe_on_end','manage_points']);

		if($crud->isEditing()){

			if($crud->form->isSubmitted()){
				if($crud->form['applied_rules'] && !$crud->form['manage_points']){
					$crud->form->displayError('manage_points','To set rules, please mark manage_points on');
				}
			}

			$crud->form->getElement('applied_rules')
					->setAttr('multiple',true)
					->set(explode(",",$crud->form->model['applied_rules']))
					->setModel('xepan\base\Rules')
					;
		}

	}
}