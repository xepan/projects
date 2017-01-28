<?php
namespace xepan\projects;

class page_test extends \Page{
	function init(){
		parent::init();

		$task = $this->add('xepan\projects\Model_Task');
		// $task->reminder();
		$task->recurring();
	}
}