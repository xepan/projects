<?php

namespace xepan\projects;

class Model_Comment extends \xepan\base\Model_Table
{	
	public $table = "projectcomment";
	public $acl = false;

	function init()
	{
		parent::init();
		
		$this->hasOne('xepan\projects\comment','task_id');
		$this->hasOne('xepan\hr\Employee','employee_id');
		$this->addField('comment');
		
	}
}