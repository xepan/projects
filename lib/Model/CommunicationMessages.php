<?php

namespace xepan\projects;

class Model_CommunicationMessages extends \xepan\base\Model_Table
{	
	function init()
	{
		parent::init();
	}
/**
Project Application
*/
	//Model_Task
	function afterInsert(){
		$this->app->employee->
		addActivity("Comment On Task: '".$task_name."' Comment By'".$this->app->employee['name']."'",null, $this['employee_id'] /*Related Contact ID*/,null,null,null)->
		notifyTo([$this['employee_id'],$task_created_by]," Comment : '".$this['comment']."' :: Commented by '".$this->app->employee['name']."' :: On Task '".$task_name."' ");
	}
	
/**
HR Application
*/
/**
Commerce Application
*/
/**
Marketing Application
*/
/**
Account Application
*/
/**
Production Application
*/
/**
CRM Application
*/
/**
CMS Application
*/
/**
BLOG Application
*/
}
