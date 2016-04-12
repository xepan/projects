<?php

namespace xepan\projects;

class View_TopView extends \View{
	function init(){
		parent::init();

	}

	// function setModel($model){
		
	// 	$m = parent::setModel($model);
		
	// 	return $m;
	// }

	function defaultTemplate(){
		return['view\topview'];
	}
}