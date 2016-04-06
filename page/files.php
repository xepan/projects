<?php

namespace xepan\projects;

class page_files extends \xepan\projects\page_sidemenu{
	public $title = "files";
	function init(){
		parent::init();
		$this->add('xepan\projects\View_TopView',null,'topview');
		$this->add('xepan\projects\View_File',null,'leftview');
	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}	
}