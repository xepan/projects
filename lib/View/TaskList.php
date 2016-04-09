<?php

namespace xepan\projects;

class View_TaskList extends \CompleteLister{
	function init(){
		parent::init();

		
	}
	function formatRow(){
		$c_task=$this->add('xepan\projects\Model_Task');
		$c_task->addCondition('parent_id',$this->model->id);
		$c_task->tryLoadAny();
		
		// if($c_task['parent_id']){
			// $c_lister=$this->add('xepan\projects\View_TaskList',null,'child');
			// $c_lister->setModel($c_task);
		// }

		$this->current_row['child_task']=$c_task['task_name'];
	}

	function defaultTemplate(){
		return['view\tasklist1'];
	}
}