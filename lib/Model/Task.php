<?php

namespace xepan\projects;

class Model_Task extends \xepan\base\Model_Table
{	
	public $table = "task";
	public $acl = false;

	public $title_field ='task_name';
	
	function init()
	{
		parent::init();
		$this->hasOne('xepan\projects\Project','project_id');
		$this->hasOne('xepan\projects\ParentTask','parent_id');
		$this->hasOne('xepan\hr\Employee','employee_id');
		$this->addField('task_name');
		$this->addField('description')->type('text');
		$this->addField('deadline')->type('date');
		$this->addField('starting_date')->type('date');
		$this->hasMany('xepan\projects\Follower_Task_Association','task_id');
		$this->hasMany('xepan\projects\Comment','task_id');	
		$this->hasMany('xepan\projects\Task','parent_id',null,'SubTasks');
	}

	function getAssociatedfollowers(){
		$associated_followers = $this->ref('xepan\projects\Follower_Task_Association')
								->_dsql()->del('fields')->field('employee_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_followers)),false);
	}

	function removeAssociateFollowers(){
		$this->ref('xepan\projects\Follower_Task_Association')->deleteAll();
	}
}