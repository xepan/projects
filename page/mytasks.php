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
	
		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$task_id = $this->app->stickyGET('task_id')?:0;
			$project_id = $this->app->stickyGET('project_id');

			$p->add('xepan\projects\View_Detail',['task_id'=>$task_id,'project_id'=>$project_id]);
		});	

		$task_view->js('click')->_selector('.task-item')->univ()->frameURL('TASK DETAIL',[$this->api->url($vp->getURL()),'task_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
	
		$task_view->js('click',$task_view->js()->reload(['delete_task_id'=>$this->js()->_selectorThis()->data('id')]))->_selector('.do-delete');

		if($_GET['delete_task_id']){
			$delete_task=$this->add('xepan\projects\Model_Task');
			$delete_task->load($_GET['delete_task_id']);
			$delete_task->delete();
			$task_view->js(true,$this->js()->univ()->successMessage('Task Deleted'))->_load('jquery.nestable')->nestable(['group'=>1]);
		}
	}

	function defaultTemplate(){
		return ['page\mytask'];
	}
}