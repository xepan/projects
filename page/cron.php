<?php
namespace xepan\projects;

class page_cron extends \Page{
	function init(){
		parent::init();

		$this->add('xepan\projects\View_TaskReminder');

		$this->add('xepan\projects\Model_Task')->recurring();
	}
}