<?php

namespace xepan\projects;

class page_dashboard extends \Page{
	public $title = "Add New Project";	
	function init(){
		parent::init();

		$this->add('xepan\projects\View_TopMenu');
		$this->add('View_Info')->set('Add summery of projects Here');
	}
}