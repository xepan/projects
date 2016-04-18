<?php

namespace xepan\projects;


class Model_Task_Attachment extends \xepan\base\Model_Table{
	public $table = 'task_attachment';
	public $acl = false;

	function init(){
		parent::init();
		
		$this->hasOne('xepan\projects\Task','task_id');
		$this->add('filestore\Field_File','file_id');
	}
}