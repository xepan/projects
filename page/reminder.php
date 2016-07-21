<?php

namespace xepan\projects;


class page_reminder extends \xepan\base\Page {
	public $title="Reminder";

	function init(){
		parent::init();
		
		$this->add('xepan\projects\View_TaskReminder');
	}
}