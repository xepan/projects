<?php

namespace xepan\projects;

class page_projectlive extends \xepan\projects\page_sidemenu{
	function init(){
		parent::init();

		$project_id = $this->app->stickyGET('project_id');
		
		$model_project = $this->add('xepan\projects\Model_Project')->load($project_id);
		$model_employee = $this->add('xepan\projects\Model_Employee');

		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project,['name']);

		$project_detail_grid = $this->add('xepan\hr\Grid',null,'grid',['view\projectlive-grid']);
		$project_detail_grid->setModel($model_employee,['team_members','project_name','total_task','completed_task','pending_task','task_starting_time','time_elapsed']); 
	}

	function defaultTemplate(){
		return['view\projectlive'];
	}
}