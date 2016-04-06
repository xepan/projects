<?php

namespace xepan\projects;

class page_conversation extends \xepan\projects\page_sidemenu{
	public $title = "Conversation";
	function init(){
		parent::init();

		$this->add('xepan\projects\View_TopView',null,'topview');
		$this->add('xepan\projects\View_Conversation',null,'leftview');
	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}	
}