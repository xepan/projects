<?php

namespace xepan\projects;

class page_projectdetail extends \xepan\projects\page_sidemenu{
	public $title = "Project Detail";
	function init(){
		parent::init();

		$this->add('xepan\projects\View_TopView',null,'topview');
		$this->add('xepan\projects\View_AddTask',null,'leftview');
		$this->add('xepan\projects\View_Comment',null,'rightview');
	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}

}