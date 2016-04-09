<?php

namespace xepan\projects;

class Model_Task extends \xepan\base\Model_Table
{	
	public $table = "task";
	public $title_field ='task_name';

	public $status=['Pending','Assigned','Submitted','On-Hold','Completed'];

	public $actions =[
		'Draft'=>['view','edit','delete','submit','assign','mark_complete']
	];
	
	function init()
	{
		parent::init();

		$this->hasOne('xepan\base\Epan');
		$this->hasOne('xepan\projects\Project','project_id');
		$this->hasOne('xepan\projects\ParentTask','parent_id');
		$this->hasOne('xepan\hr\Employee','employee_id');
		$this->addField('task_name');
		$this->addField('description')->type('text');
		$this->addField('deadline')->type('date');
		$this->addField('starting_date')->type('date');
		
		$this->addField('status')->defaultValue('Draft');
		$this->addField('type');
		$this->addCondition('type','Task');

		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
		$this->hasOne('xepan\hr\Employee','created_by_id');

		$this->hasMany('xepan\projects\Follower_Task_Association','task_id');
		$this->hasMany('xepan\projects\Comment','task_id');	
		$this->hasMany('xepan\projects\Task','parent_id',null,'SubTasks');

		$this->addHook('beforeDelete',$this);


	}

	function beforeDelete(){
		$task=$this->add('xepan\projects\Model_Task');
		$task->addCondition('parent_id',$this->id);
		$task->tryloadAny();

		if($task->count()->getOne()){
			throw new \Exception("Can'not delete task its contains many sub task delete first ", 1);
			
		}
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