<?php

namespace xepan\projects;

class View_TaskDetail extends \View{
	public $task_list_view=null;
	
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['view\taskdetail'];
	}
}