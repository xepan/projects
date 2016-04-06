<?php

namespace xepan\projects;

class page_projectdashboard extends \xepan\projects\page_sidemenu{
	public $title = "Dashboard";	
	function init(){
		parent::init();

		$this->add('View_Info')->set('Graphical information about projects');
	}
}