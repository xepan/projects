<?php

namespace xepan\projects;

class View_File extends \View{
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['view\file'];
	}
}