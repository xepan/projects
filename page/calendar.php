<?php

namespace xepan\projects;

class page_calendar extends \xepan\projects\page_sidemenu{
	public $title = "Calendar";
	function init(){
		parent::init();
		$this->add('xepan\projects\View_TopView',null,'topview');
	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}	
}