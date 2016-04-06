<?php

namespace xepan\projects;

class View_TaskList extends \CompleteLister{
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['view\tasklist'];
	}
}