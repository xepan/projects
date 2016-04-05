<?php

namespace xepan\projects;

class page_newproject extends \Page{
	public $title = "Add New Project";	
	function init(){
		parent::init();

		$this->add('xepan\projects\View_NewProject');	
	}
}