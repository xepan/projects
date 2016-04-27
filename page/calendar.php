<?php

namespace xepan\projects;

class page_calendar extends \xepan\projects\page_sidemenu{
	public $title = "Calendar";
	function init(){
		parent::init();
		$project_id = $this->app->stickyGET('project_id');
		
		$model_project = $this->add('xepan\projects\Model_Formatted_Project')->load($project_id);


		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project,['name']);
						
	}

	function render(){
		$project_id = $this->app->stickyGET('project_id');
		$model_task = $this->add('xepan\projects\Model_Task')->addCondition('project_id',$project_id)->addCondition('status','Pending')->addCondition('employee_id',$this->app->employee->id);
		$rows = [];
		foreach ($model_task as $task) {
			$rows[$task->id] = [];
			$rows[$task->id]['title'] = $task['task_name'];
			$rows[$task->id]['start'] = $task['created_at']?:'1970-01-01';
			$rows[$task->id]['end'] = $task['deadline'];
		}
		
		$task = array_values($rows);
	
		$this->js(true)->_load('fullcalendar.min')->_load('xepan-taskscheduler');
		$this->js(true)->_selector('#calendar')->univ()->taskDate($task);
		parent::render();

	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}	
}