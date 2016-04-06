<?php

namespace xepan\projects;

class View_Task extends \View{
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['view\taskdetail'];
	}
}