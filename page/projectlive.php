<?php

namespace xepan\projects;

class page_projectlive extends \xepan\projects\page_sidemenu{
	public $title = "Trace Employee";
	public $breadcrumb=['Home'=>'index','Project'=>'xepan_projects_project','Status'=>'#'];
	function init(){
		parent::init();

		$project_id = $this->app->stickyGET('project_id');
		
		$model_project = $this->add('xepan\projects\Model_Formatted_Project');
		
		if($project_id){
			$model_project->load($project_id);
		}

		// $top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		// $top_view->setModel($model_project,['name']);

		$model_employee = $this->add('xepan\projects\Model_Employee');
		$model_employee->getElement('pending_tasks_count')->destroy();
		$model_employee->addExpression('pending_tasks_count')->set(function ($m,$q)use($project_id){
			return $m->refSQL('xepan\projects\Task')
						->addCondition('status','Pending')
						->addCondition('project_id',$project_id)
						->count();
		});
		
		$project_detail_grid=$this->add('xepan\hr\Grid',null,'grid',['view\status']);
		$project_detail_grid->add('xepan\base\Controller_Avatar',['options'=>['size'=>40,'border'=>['width'=>0]],'name_field'=>'name','default_value'=>'']);
		$project_detail_grid->addPaginator(50);
		$project_detail_grid->addQuickSearch(['name']);
		$project_detail_grid->setModel($model_employee,['name','running_task','project','pending_tasks_count','running_task_since']); 
		
		$project_detail_grid->js('click')->_selector('.do-view-project-live')->univ()->frameURL('Employee Project Status',[$this->api->url('xepan_projects_dailyanalysis'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);

	}

	function defaultTemplate(){
		return['view\projectlive'];
	}
}