<?php

namespace xepan\projects;

class View_TopView extends \View{
	function setModel($m){
		$m = parent::setModel($m);

		$complete = $this->add('xepan\projects\View_Progressbar',null,'totalprogress',['view\progressbar']);
		$complete->template->set('total_task',$m['total_task']);
		$complete->template->set('completed_percentage',$m['completed_percentage']);
		$complete->template->set('color',$m['color']);
		$complete->template->set('critical_completed_percentage',abs($m['critical_completed_percentage']-100));
		$complete->template->set('high_completed_percentage',abs($m['high_completed_percentage']-100));
		$complete->template->set('medium_completed_percentage',abs($m['medium_completed_percentage']-100));
		$complete->template->set('low_completed_percentage',abs($m['low_completed_percentage']-100));
		$complete->template->set('title','Total Tasks');

		$self = $this->add('xepan\projects\View_Progressbar',null,'selfprogress',['view\progressbar']);
		$self->template->set('self_task',$m['self_task']);
		$self->template->set('self_percentage',$m['self_percentage']);
		$self->template->set('self_color',$m['self_color']);
		$self->template->set('critical_self_percentage',abs($m['critical_self_percentage']-100));
		$self->template->set('high_self_percentage',abs($m['high_self_percentage']-100));
		$self->template->set('medium_self_percentage',abs($m['medium_self_percentage']-100));
		$self->template->set('low_self_percentage',abs($m['low_self_percentage']-100));
		$self->template->set('title','Your Tasks');

		return $m;
	}


	function defaultTemplate(){
		return['view\topview'];
	}
}