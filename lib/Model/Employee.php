<?php

namespace xepan\projects;

class Model_Employee extends \xepan\hr\Model_Employee{

	public $status = ['Active','InActive'];
	public $actions = ['Active'=>['view','manage_regular_tasks','add_points'],'InActive'=>['view']];
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
			return $m->add('xepan\projects\Model_Task')
						->addCondition('assign_to_id',$q->getField('id'))
						->addCondition('status',['Pending','Submitted','Assigned','Inprogress'])
						->addCondition('type',['Task','Followup','Reminder'])
						->count();
		})->sortable(true);



		$this->addExpression('performance')->set("'Todo'");
	}

	function page_manage_regular_tasks($p){
		$vp = $p->add('VirtualPage');
		$vp->set([$this,'copy_tasks_from_other_employee']);
		

		$p->add('Button')->set('Copy from Other Employee')
			->js('click')->univ()->frameURL('Copy Tasks',$vp->getURL());

		$tasks = $p->add('xepan\projects\Model_Task');
		$tasks->addCondition('assign_to_id',$this->id);
		$tasks->addCondition('is_regular_work',true);
		$tasks->addCondition('type','Task');

		$tasks->getElement('applied_rules')->display(['form'=>'xepan\base\NoValidateDropDown']);

		$tasks->getElement('starting_date')->defaultValue($this->app->now);

		$crud = $p->add('xepan\base\CRUD');
		$crud->addClass('temp');
		$crud->js('reload')->reload();
		$crud->setModel($tasks,['task_name','description','describe_on_end','applied_rules','manage_points'],['task_name','description','describe_on_end','manage_points','assign_employee_status','status','assign_to_id','created_by_id']);

		$crud->grid->removeColumn('assign_employee_status');
		$crud->grid->removeColumn('status');
		$crud->grid->removeColumn('assign_to_id');
		$crud->grid->removeColumn('created_by_id');

		if($crud->isEditing()){

			if($crud->form->isSubmitted()){
				if($crud->form['applied_rules'] && !$crud->form['manage_points']){
					$crud->form->displayError('manage_points','To set rules, please mark manage_points on');
				}
			}
			$r_m = $this->add('xepan\base\Model_Rules');
			$r_m->addExpression('rule_with_group')->set(function($m,$q){
				return $q->expr('CONCAT([0]," - ",[1])',[$m->getElement('rulegroup'),$m->getElement('name')]);
			});

			$r_m->title_field = 'rule_with_group';

			$crud->form->getElement('applied_rules')
					->setAttr('multiple',true)
					->set(explode(",",$crud->form->model['applied_rules']))
					->setModel($r_m)
					;
		}

	}

	function copy_tasks_from_other_employee($p){
		$form = $p->add('Form');
		$form->addField('xepan\hr\Employee','copy_from');
		$form->addSubmit('Copy');

		if($form->isSubmitted()){
			if(!$form['copy_from']) $form->displayError('copy_from','Must be defined');
			if($form['copy_from'] == $this->id) $form->displayError('copy_from','Must not be seft employee');
			$tasks = $p->add('xepan\projects\Model_Task');
			$tasks->addCondition('assign_to_id',$form['copy_from']);
			$tasks->addCondition('is_regular_work',true);
			$tasks->addCondition('type','Task');

			foreach ($tasks as $o_e_t) {
				$new_tasks = $p->add('xepan\projects\Model_Task');
				$new_tasks['assign_to_id']=$this->id;
				$new_tasks['is_regular_work']=true;
				$new_tasks['type']='Task';
				$new_tasks['task_name']=$o_e_t['task_name'];
				$new_tasks['description']=$o_e_t['description'];
				$new_tasks['describe_on_end']=$o_e_t['describe_on_end'];
				$new_tasks['applied_rules']=$o_e_t['applied_rules'];
				$new_tasks['manage_points']=$o_e_t['manage_points'];
				$new_tasks['starting_date']=$this->app->now;
				$new_tasks->save();
			}

			$form->js(null,$form->js()->_selector('.temp')->trigger('reload'))->univ()->closeDialog()->execute();
		}
	}


	function page_add_points($p){
		$p->add('View')->set('Add admin points for today, Login from superuser and check trace employee to manage old points');
		$point_system_m = $this->add('xepan\base\Model_PointSystem');
		$point_system_m->addCondition('contact_id',$this->id);
		$point_system_m->addCondition('timesheet_id',-1);
		// $point_system_m->addCondition('created_at_date',$this->app->today);
		$point_system_m->getElement('created_at_date')->caption('On Date');

		$point_system_m->addHook('beforeSave',function($m){
			$m['created_by_id'] = $this->app->employee->id; // to save last updater
		});


		$crud= $p->add('xepan\hr\CRUD',['pass_acl'=>true]);
		
		$crud->setModel($point_system_m,['created_at','rule_option_id','qty','remarks'],['created_at_date','rule_option','qty','score','remarks','created_by']);

		if($crud->isEditing()){
			$crud->form->getElement('rule_option_id')->getModel()->title_field='name_with_score';
		}

		$crud->grid->addFormatter('rule_option','wrap');
	}
}