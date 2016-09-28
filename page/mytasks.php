<?php

namespace xepan\projects;

class page_mytasks extends \xepan\base\Page{
	public $title = "My Tasks";
	function init(){
		parent::init();

		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('employee_id',$this->app->employee->id);
		$task->setOrder('created_at','desc');

		$task_view = $this->add('xepan\projects\View_TaskList',null,'my_task_view');
		$task_view->setModel($task);
		$task_view->add('xepan\hr\Controller_ACL',['action_btn_group'=>'xs']);
	}

	function defaultTemplate(){
		return ['page\mytask'];
	}
}