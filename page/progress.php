<?php

namespace xepan\projects;

class page_progress extends \xepan\projects\page_sidemenu{
	public $title = "Progress";
	function init(){
		parent::init();	

		$this->add('xepan\projects\View_TopView',null,'topview');
		$this->add('xepan\projects\View_Progress',null,'leftview');
	}

	function defaultTemplate(){
		return['page\projectdetail'];	
	}	
}