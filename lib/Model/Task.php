<?php

namespace xepan\projects;

class Model_Task extends \xepan\base\Model_Table
{	
	public $table = "task";
	public $acl = false;
	
	function init()
	{
		parent::init();
		$this->hasOne('xepan\projects\Model_Project','project_id');
		$this->addField('task_name');
		$this->addField('comment');	
	}
}