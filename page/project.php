<?php

namespace xepan\projects;

class page_project extends \xepan\projects\page_sidemenu{
	public $title = "Add/Edit Project";
	function init(){
		parent::init();

		$project = $this->add('xepan\projects\Model_Project');
		$crud=$this->add('xepan\hr\CRUD',null,null,['view\project-grid']);
		$crud->setModel($project);

		
	}	
}