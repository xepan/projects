<?php
namespace xepan\projects;
class page_sidemenu extends \Page{
	function init(){
		parent::init();
		$this->app->side_menu->addItem('Project Dashboard','xepan_projects_projectdashboard');
		$this->app->side_menu->addItem('Add/Edit Project','xepan_projects_project');
	}
}