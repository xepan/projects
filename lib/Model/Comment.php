<?php

namespace xepan\projects;

class Model_Comment extends \xepan\base\Model_Table
{	
	public $table = "projectcomment";
	public $acl = false;

	function init()
	{
		parent::init();
		
		$this->hasOne('xepan\projects\task','task_id');
		$this->hasOne('xepan\hr\Employee','employee_id');
		$this->addField('comment');
		$this->addField('on_action')->enum(['General','Pending','Submitted','Completed','Reopened','Received','Rejected','Inprogress']);
		
		$this->addHook('beforeSave',[$this,'notifyComment']);
	}

	function notifyComment(){
		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('id',$this['task_id']);
		$task->tryLoadAny();

		if($task->loaded())
			$task_name = $task['task_name'];

		$this->app->employee->
		addActivity("Comment On Task: '".$task_name."' Comment By'".$this->app->employee['name']."'",null, $this['employee_id'] /*Related Contact ID*/,null,null,null)->
		notifyTo([$this['employee_id']],"Comment on Task : " . $task_name);
	}
}