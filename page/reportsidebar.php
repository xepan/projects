<?php

namespace xepan\projects;

class page_reportsidebar extends \xepan\base\Page{
	function init(){
		parent::init();

		$this->app->side_menu->addItem(['Project Report','icon'=>'fa fa-sitemap'],'xepan_projects_projectreport')->setAttr(['title'=>'Project Report']);
		$this->app->side_menu->addItem(['Project & Task Report','icon'=>'fa fa-tasks'],'xepan_projects_projectandtaskreport')->setAttr(['title'=>'Project And Task Report']);
		$this->app->side_menu->addItem(['Task & Employee Report','icon'=>'fa fa-user'],'xepan_projects_taskandemployeereport')->setAttr(['title'=>'Task And Employee Report']);
	}
}