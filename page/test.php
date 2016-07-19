<?php
namespace xepan\projects;

class page_test extends \Page{
	function init(){
		parent::init();
		
		$task = $this->add('xepan\projects\Model_Task');
		$crud = $this->add('CRUD');
		$crud->setModel($task)->addCondition('set_reminder',true);
		
		if($crud->isEditing()){
			$crud->form->getElement('remind_via')
							->addClass('multiselect-full-width')
							->setAttr(['multiple'=>'multiple']);
		}

		$task->reminder();

	}
}