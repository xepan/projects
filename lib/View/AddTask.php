<?php

namespace xepan\projects;

class View_AddTask extends \View{
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['view\addtask'];
	}
}