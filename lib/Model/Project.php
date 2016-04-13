<?php

namespace xepan\projects;

class Model_Project extends \xepan\base\Model_Table
{	
	public $table = "project";
	
	public $status=[
		'Running',
		'Onhold',
		'Completed'
	];
	
	public $actions=[
		'Running'=>['view','edit','delete','onhold','completed'],
		'Onhold'=>['view','edit','delete','running','completed'],
		'Completed'=>['view','edit','delete','running']
	];

	function init()
	{
		parent::init();
		
		$this->addField('name');
		$this->addField('description');	
		$this->addField('status')->defaultValue('Draft');
		$this->addField('type');
		
		$this->addCondition('type','project');

		$this->hasMany('xepan\projects\Task','project_id');
		$this->hasMany('xepan\projects\Team_Project_Association','project_id');

		$this->addHook('beforeDelete',$this);
	}

	function getAssociatedTeam(){
		$associated_team = $this->ref('xepan\projects\Team_Project_Association')
								->_dsql()->del('fields')->field('employee_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_team)),false);
	}

	function removeAssociateTeam(){
		$this->ref('xepan\projects\Team_Project_Association')->deleteAll();
	}

	function beforedelete(){
		$task=$this->add('xepan\projects\Model_Task');
		$task->addCondition('project_id',$this->id);
		$task->tryLoadAny();

		if($task->count()->getOne()){
			throw new \Exception("Can'not Delete Project, First delete associated tasks", 1);
			
		}
	}
}