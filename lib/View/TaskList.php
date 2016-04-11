<?php

namespace xepan\projects;

class View_TaskList extends \xepan\base\Grid{

	function init(){
		parent::init();

		
	}
	function formatRow(){
		$sub_tasks=$this->add('xepan\projects\Model_Task',['name'=>'task_'.$this->model->id]);
		$sub_tasks->addCondition('parent_id',$this->model->id);
		if($sub_tasks->count()->getOne() > 0){
			$sub_v =$this->add('xepan\projects\View_TaskList',null,'sub_tasks',['view/tasklist1','nested_template']);
			$sub_v->setModel($sub_tasks);
			$sub_v->add('xepan\hr\Controller_ACL');
			$sub_v->add('xepan\base\Controller_Avatar',['options'=>['size'=>20],'name_field'=>'employee','default_value'=>'']);
			$this->current_row_html['sub_tasks']= $sub_v->getHTML();
		}else{
			$this->current_row_html['sub_tasks']= "";
		}
		return parent::formatRow();
	}

	function defaultTemplate(){
		return['view/tasklist1'];
	}
}