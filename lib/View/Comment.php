<?php

namespace xepan\projects;

class View_Comment extends \View{
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['view\comment'];
	}
}