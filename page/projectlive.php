<?php

namespace xepan\projects;

class page_projectlive extends \xepan\projects\page_sidemenu{
	function init(){
		parent::init();

		$project_id = $this->app->stickyGET('project_id');
		
		$model_project = $this->add('xepan\projects\Model_Project')->load($project_id);


		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project,['name']);

		$model_employee = $this->add('xepan\projects\Model_Employee');
		$model_employee->getElement('pending_tasks_count')->destroy();
		$model_employee->addExpression('pending_tasks_count')->set(function ($m,$q)use($project_id){
			return $m->refSQL('xepan\projects\Task')
						->addCondition('status','Pending')
						->addCondition('project_id',$project_id)
						->count();
		});
		
		$project_detail_grid = $this->add('xepan\hr\Grid',null,'grid');
		$project_detail_grid->setModel($model_employee,['name','running_task','project','pending_tasks_count','running_task_since']); 
	}

	function defaultTemplate(){
		return['view\projectlive'];
	}
}