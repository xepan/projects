<?php

namespace xepan\projects;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_projects';

	function init(){
		parent::init();
		
		$this->routePages('xepan_projects');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('../vendor/xepan/projects/');

		if($this->app->is_admin){
			$m = $this->app->top_menu->addMenu('Projects');
			$m->addItem('Dashboard','xepan_projects_projectdashboard');
		}
	}
}