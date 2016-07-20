<?php
namespace xepan\projects;

class page_test extends \Page{
	function init(){
		parent::init();

		$reminder_view = $this->add('xepan\projects\View_TaskReminder');
	}
}