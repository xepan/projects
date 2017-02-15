<?php

namespace xepan\projects;


class Model_Task_Attachment extends \xepan\base\Model_Table{
	public $table = 'task_attachment';
	public $acl = false;

	function init(){
		parent::init();
		
		$this->hasOne('xepan\projects\Task','task_id');
		
		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);

		$this->add('xepan\filestore\Field_File','file_id');

		$this->addExpression('thumb_url')->set(function($m,$q){
			return $q->expr('[0]',[$m->getElement('file')]);
		});

		$this->addExpression('filename')->set(function($m,$q){
			return $m->refSQL('file_id')->fieldQuery('original_filename');
		});

		$this->addHook('beforeDelete',$this);
	}

	function beforeDelete(){
		
		$file = $this->add('xepan\filestore\Model_File');
		$file->addCondition('id',$this['file_id']);

		$file->each(function($m){
			$m->delete();
		});
	}
}
