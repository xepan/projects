<?php

namespace xepan\projects;

class Model_Comment extends \xepan\base\Model_Table
{	
	public $table = "projectcomment";

	function init()
	{
		parent::init();
		
		$this->hasOne('xepan\projects\comment','task_id');
		$this->addField('comment');
		$this->addField('name');
	}
}