<?php

namespace xepan\projects;

class page_activity_report extends \xepan\base\Page{
	public $title = "Activity Report :: ";
	function init(){
		parent::init();
		
		$this->title .= 'Task';
	}
}