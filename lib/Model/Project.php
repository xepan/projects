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
		$this->addField('type');

		$this->addCondition('type','project');	
	}
}