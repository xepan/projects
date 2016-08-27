<?php

namespace xepan\projects;

class page_projectdashboard extends \xepan\projects\page_sidemenu{
	public $title = "Dashboard";	
	function init(){
		parent::init();

		$project = $this->add('xepan\projects\Model_project');
		$tasks = $this->add('xepan\projects\Model_task');
		
		$this->template->trySet('projects',$project->count());
		$this->template->trySet('tasks',$tasks->count());
		
		$tasks->addCondition('status','Pending');
		$this->template->trySet('pending_tasks',$tasks->count());

		$completed_tasks = $this->add('xepan\projects\Model_task');
		$completed_tasks->addCondition('status','Completed');
		$this->template->trySet('completed_tasks',$tasks->count());

		$model_formatted_projects = $this->add('xepan\projects\Model_Formatted_Project');
		$model_formatted_projects->addCondition('status','Active');

		$project_overview = $this->add('xepan\base\Grid',null,'project_overview',['view\dashboard\projectoverview']);
		$project_overview->setModel($model_formatted_projects);		
	}

	function defaultTemplate(){
		return ['page\dashboard'];
	}
}