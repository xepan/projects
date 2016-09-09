<?php
namespace xepan\projects;
class page_sidemenu extends \xepan\base\Page{
	
	function init(){
		parent::init();

		$this->app->side_menu->addItem(['Dashboard','icon'=>' fa fa-dashboard'],'xepan_projects_projectdashboard');
	}
}
