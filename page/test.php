<?php
namespace xepan\projects;

class page_test extends \Page{
	function init(){
		parent::init();

		$this->add('xepan\projects\View_InstantTaskFeed');
	}
}