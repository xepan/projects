<?php

namespace xepan\projects;

class View_Task extends \View{
	function init(){
		parent::init();

		$this->add('View')->set($_GET['task_id']);

	}

	function defaultTemplate(){
		return['view\taskdetail'];
	}
}