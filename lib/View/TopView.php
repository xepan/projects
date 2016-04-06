<?php

namespace xepan\projects;

class View_TopView extends \View{
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['view\topview'];
	}
}