<?php

namespace xepan\projects;

class Widget_TodaysTaskStatus extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->report->enableFilterEntity('Employee');
		$this->view = $this->add('View',null,null,['widget\todays-task-status']);
	}

	function recursiveRender(){
		$employee_id = isset($this->report->employee)?$this->report->employee : $this->app->employee->id;
		
		$employee = $this->add('xepan\hr\Model_Employee');
		$employee->load($employee_id);

		$this->view->template->trySet('employee',$employee['name']);
		$this->view->template->trySet('date',$this->app->today);

		$task_created_m = $this->add('xepan\projects\Model_Task');
		$task_created_m->addCondition('type','Task');
		$task_created_m->addCondition('created_by_id',$employee_id);
		$task_created_m->addCondition('created_at','>=',$this->app->today);
		$task_created_count = $task_created_m->count();
		$this->view->template->trySet('created',$task_created_count);

		$task_pending_m = $this->add('xepan\projects\Model_Task');
		$task_pending_m->addCondition('type','Task');
		$task_pending_m->addCondition('assign_to_id',$employee_id);
		$task_pending_m->addCondition('status','Pending');
		$task_pending_count = $task_pending_m->count();
		$this->view->template->trySet('pending',$task_pending_count);

		$task_toreceive_m = $this->add('xepan\projects\Model_Task');
		$task_toreceive_m->addCondition('type','Task');
		$task_toreceive_m->addCondition('assign_to_id',$employee_id);
		$task_toreceive_m->addCondition('status','Assigned');
		$task_toreceive_count = $task_toreceive_m->count();
		$this->view->template->trySet('to_receive',$task_toreceive_count);

		$task_submitted_m = $this->add('xepan\projects\Model_Task');
		$task_submitted_m->addCondition('type','Task');
		$task_submitted_m->addCondition('assign_to_id',$employee_id);
		$task_submitted_m->addCondition('status','Submitted');
		$task_submitted_count = $task_submitted_m->count();
		$this->view->template->trySet('submitted',$task_submitted_count);

		$task_completed_m = $this->add('xepan\projects\Model_Task');
		$task_completed_m->addCondition('type','Task');
		$task_completed_m->addCondition('assign_to_id',$employee_id);
		$task_completed_m->addCondition('status','Completed');
		$task_completed_m->addCondition('created_at','>=',$this->app->now);
		$task_completed_count = $task_completed_m->count();
		$this->view->template->trySet('completed',$task_completed_count);

		$task_assigned_m = $this->add('xepan\projects\Model_Task');
		$task_assigned_m->addCondition('type','Task');
		$task_assigned_m->addCondition('created_by_id',$employee_id);
		$task_assigned_m->addCondition('assign_to_id','<>',$employee_id);
		$task_assigned_m->addCondition('created_at','>=',$this->app->now);
		$task_assigned_count = $task_assigned_m->count();
		$this->view->template->trySet('assigned',$task_assigned_count);

		$this->view->js('click')->_selector('.do-view-todaystask')->univ()->frameURL('Todays Task Status',[$this->app->url('xepan_projects_widget_todaystask'),['employee_id'=>$employee_id]]);
		
		parent::recursiveRender();
	}
}