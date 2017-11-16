<?php

namespace xepan\projects;

class page_minitask extends \xepan\base\Page{
	function init(){
		parent::init();

		$this->add('xepan\projects\View_InstantTaskFeed');	
	}
}