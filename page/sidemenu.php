<?php
namespace xepan\projects;
class page_sidemenu extends \Page{
	function init(){
		parent::init();

		$this->app->side_menu->addItem(['Dashboard','icon'=>' fa fa-dashboard','badge'=>['10','swatch'=>' label label-primary label-circle pull-right']],'xepan_projects_projectdashboard');

		$this->app->side_menu->addItem(['Add/Edit Project','icon'=>' fa fa-edit','badge'=>['10','swatch'=>' label label-primary label-circle pull-right']],'xepan_projects_project');
	}
}