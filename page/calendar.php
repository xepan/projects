<?php

namespace xepan\projects;

class page_calendar extends \xepan\projects\page_sidemenu{
	public $title = "Calendar";
	function init(){
		parent::init();
		$project_id = $this->app->stickyGET('project_id');
		
		$model_project = $this->add('xepan\projects\Model_Project');
		
		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project)->load($project_id);
	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}	
}