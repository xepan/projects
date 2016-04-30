<?php

namespace xepan\projects;

class page_files extends \xepan\projects\page_sidemenu{
	public $title = "files";
	function init(){
		parent::init();
		$project_id = $this->app->stickyGET('project_id');
		
		$model_project = $this->add('xepan\projects\Model_Project');
		
		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project)->load($project_id);
		$view = $this->add('xepan\projects\View_File',null,'leftview');

	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}

	function render(){
		$this->js(true)->_load('gantt\ganttUtilities')
					   ->_load('gantt\ganttTask')
					   ->_load('gantt\ganttDrawerSVG')
					   ->_load('gantt\ganttGridEditor')
					   ->_load('gantt\ganttMaster')
					   ->_load('gantt\ganttTask');

	    $this->js(true)->_selector('#calendar')->GanttMaster();
		parent::render();
	}	
}