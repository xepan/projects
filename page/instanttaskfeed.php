<?php

namespace xepan\projects;

class page_instanttaskfeed extends \xepan\base\Page{
	function init(){
		parent::init();

		$this->add('xepan\projects\View_InstantTaskFeed');
	}
}