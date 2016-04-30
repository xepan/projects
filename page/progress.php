<?php

namespace xepan\projects;

class page_progress extends \xepan\projects\page_sidemenu{
	public $title = "Progress";
	function init(){
		parent::init();	

		$project_id = $this->app->stickyGET('project_id');
		
		$model_project = $this->add('xepan\projects\Model_Project');
		
		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project)->load($project_id);
		$this->add('xepan\projects\View_Progress',null,'leftview');
	}

	function defaultTemplate(){
		return['page\projectdetail'];	
	}	
}