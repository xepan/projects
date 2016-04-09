<?php

namespace xepan\projects;

class View_TaskList extends \CompleteLister{
	function init(){
		parent::init();

		
	}
	function formatRow(){
		$sub_tasks=$this->add('xepan\projects\Model_Task',['name'=>'task_'.$this->model->id]);
		$sub_tasks->addCondition('parent_id',$this->model->id);
		if($sub_tasks->count()->getOne() > 0){
			$sub_v =$this->add('xepan\projects\View_TaskList',null,'sub_tasks',['view/tasklist1','nested_template']);
			$sub_v->setModel($sub_tasks);
			$this->current_row_html['sub_tasks']= $sub_v->getHTML();
		}
		return parent::formatRow();
	}

	function defaultTemplate(){
		return['view/tasklist1'];
	}
}