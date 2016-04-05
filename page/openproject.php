<?php

namespace xepan\projects;

class page_openproject extends \Page{
	function init(){
		parent::init();

		$clicked_link = "task";

		if($_GET['link']){
			$clicked_link = $_GET['link'];
		}		

		$this->add('xepan\projects\View_TopMenu',null,'topmenu');		
		
		if($clicked_link=="task"){
			$this->add('xepan\projects\View_AddTask',null,'add');
			$this->add('xepan\projects\View_Comment',null,'comment');
		}

		if($clicked_link=="calendar"){

		}

		if($clicked_link=="conversation"){
			$this->add('xepan\projects\View_Conversation',null,'conversation');
		}

		if($clicked_link=="progress"){

			$this->add('xepan\projects\View_Progress',null,'progress');
		}


		if($clicked_link=="files"){

			$this->add('xepan\projects\View_File',null,'file');
		}		

	}

	function defaultTemplate(){
		return['page\dashboard'];
	}
}