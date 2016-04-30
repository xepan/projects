<?php

namespace xepan\projects;

class View_Dashboard extends \View{
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['view\dashboard'];
	}
}