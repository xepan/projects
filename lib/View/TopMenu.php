<?php

namespace xepan\projects;

class View_TopMenu extends \View{
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['view\topmenu'];
	}
}