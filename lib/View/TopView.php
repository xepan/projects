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

		// $this->js('click')->_selector('.do-view-project-details')->univ()->frameURL('Project Details',[$this->api->url('xepan_projects_projectdetail'),'project_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		$this->js('click')->_selector('.do-view-project-lives-details')->univ()->frameURL('Employee Status',[$this->api->url('xepan_projects_projectlive'),'project_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		$this->js('click')->_selector('.do-view-project-all-task-lists')->univ()->frameURL('Task/Request List',[$this->api->url('xepan_projects_projecttasklist'),'project_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		// $this->js('click')->_selector('.do-view-project-calendar')->univ()->frameURL('Project Calendar',[$this->api->url('xepan_projects_calendar'),'project_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		// $this->js('click')->_selector('.do-view-project-progress')->univ()->frameURL('Project Conversation',[$this->api->url('xepan_projects_progress'),'project_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		// $this->js('click')->_selector('.do-view-project-conversion')->univ()->frameURL('Project Files',[$this->api->url('xepan_projects_conversation'),'project_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		return $m;
	}


	function defaultTemplate(){
		return['view\topview'];
	}
}