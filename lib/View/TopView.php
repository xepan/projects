<?php

namespace xepan\projects;

class View_TopView extends \View{
	function setModel($m){
		$m = parent::setModel($m);

		$complete = $this->add('xepan\projects\View_Progressbar',null,'totalprogress',['view\progressbar']);
		$complete->template->set('total_task',$m['total_task']);
		$complete->template->set('completed_percentage',$m['completed_percentage']);
		$complete->template->set('color',$m['color']);

		$self = $this->add('xepan\projects\View_Progressbar',null,'selfprogress',['view\progressbar']);
		$self->template->set('self_task',$m['self_task']);
		$self->template->set('self_percentage',$m['self_percentage']);
		$self->template->set('self_color',$m['self_color']);

		return $m;
	}


	function defaultTemplate(){
		return['view\topview'];
	}
}