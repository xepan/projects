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
		$this->hasOne('xepan\hr\Employee','employee_id')->defaultValue($this->app->employee->id);
		$this->addField('comment');
		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
		$this->addField('action')->caption('on_action');
		$this->addField('is_seen_by_creator')->type('boolean')->defaultValue(false);
		$this->addField('is_seen_by_assignee')->type('boolean')->defaultValue(false);

		$this->setOrder('created_at','desc');
		
		$this->addExpression('on_action')->set(function($m,$q){
			return $q->expr('[0]',[$m->getElement('action')]);

			// why using this code
			// $comment = $this->add('xepan\projects\Model_Comment');
			// $comment->addCondition('id',$m->getElement('id'));
			// $comment->setLimit(1);
			// return $comment->fieldQuery('action');
		});

		$this->addHook('afterInsert',$this);
		$this->addHook('beforeSave',[$this,'updateUnseenCount']);
	}

	function updateUnseenCount(){
		$task = $this->add('xepan\projects\Model_Task');
		$task->tryLoad($this['task_id']);		

		if($task['created_by_id'] == $this->app->employee->id){
			$task['assignee_unseen_comment_count'] =$task['assignee_unseen_comment_count'] + 1;
		}else{
			$task['creator_unseen_comment_count'] =$task['creator_unseen_comment_count'] + 1;
		}

		if(!$this->loaded()){
			$task['comment_count'] = $task['comment_count'] + 1;
		}

		$task['last_comment_time'] = $this->app->now;

		$task->save();
	}

	function isSeenTrue(){
		$task = $this->add('xepan\projects\Model_Task');
		$task->tryLoad($this['task_id']);

		if($task->loaded()){
			if($task['created_by_id'] == $this['employee_id'])
				$this['is_seen_by_creator'] = true;
				
			if($task['assign_to_id'] == $this['employee_id'])
				$this['is_seen_by_assignee'] = true;
		}
	}

	function afterInsert(){
		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('id',$this['task_id']);
		$task->tryLoadAny();

		if($task->loaded()){
			$task_name = $task['task_name'];
			$task_created_by = $task['created_by_id'];
			$task['updated_at'] = $this->app->now;
			$task->save();

			$notify_emp = [];
			if($task_created_by !=  $this->app->employee->id)
				$notify_emp []= $task_created_by;
			if($task['assign_to_id'] != $this->app->employee->id)
				$notify_emp []= $task['assign_to_id'];
			
			$this->app->employee->
			addActivity("Comment On Task: '".$task_name."' Comment By'".$this->app->employee['name']."'",null, $this['employee_id'] /*Related Contact ID*/,null,null,null)->
			notifyTo($notify_emp," Comment : '".$this['comment']."' :: Commented by '".$this->app->employee['name']."' :: On Task '".$task_name."' ");
		}

	}
}