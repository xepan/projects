<?php

namespace xepan\projects;

class page_dashboard extends \Page{
	public $title = "Add New Project";	
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['page\dashboard'];
	}
}