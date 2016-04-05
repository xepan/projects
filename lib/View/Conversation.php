<?php

namespace xepan\projects;

class View_Conversation extends \View{
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['view\conversation'];
	}
}