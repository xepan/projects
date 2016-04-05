<?php

namespace xepan\projects;

class View_NewProject extends \View{
	function init(){
		parent::init();

	}

	function defaultTemplate(){
		return['view\newproject'];
	}
}