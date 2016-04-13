<?php

namespace xepan\projects;

class View_TopView extends \View{
	function init(){
		parent::init();

		$model_formatted_project = $this->add('xepan\projects\Model_Formatted_Project')->load($_GET['project_id']);

		$this->add('xepan\projects\View_Progressbar',null,'totalprogress',['view\progressbar'])->setModel($model_formatted_project,['total_task','completed_percentage','color']);
		$this->add('xepan\projects\View_Progressbar',null,'selfprogress',['view\progressbar'])->setModel($model_formatted_project,['self_task','self_percentage','self_color']);
	}


	function defaultTemplate(){
		return['view\topview'];
	}
}