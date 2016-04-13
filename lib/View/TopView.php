<?php

namespace xepan\projects;

class View_TopView extends \View{
	function init(){
		parent::init();

		$model_formatted_project = $this->add('xepan\projects\Model_Formatted_Project')->load($_GET['project_id']);

		$complete = $this->add('xepan\projects\View_Progressbar',null,'totalprogress',['view\progressbar']);
		$complete->setModel($model_formatted_project,['total_task','completed_percentage','color']);
		$complete->template->trydel('self_task');
		$complete->template->trydel('self_percentage');
		$complete->template->trydel('self_color');

		$self = $this->add('xepan\projects\View_Progressbar',null,'selfprogress',['view\progressbar']);
		$self->setModel($model_formatted_project,['self_task','self_percentage','self_color']);
		$self->template->trydel('total_task');
		$self->template->trydel('completed_percentage');
		$self->template->trydel('color');
	}


	function defaultTemplate(){
		return['view\topview'];
	}
}