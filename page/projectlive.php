<?php

namespace xepan\projects;

class page_projectlive extends \xepan\projects\page_sidemenu{
	public $title = "Trace Employee";
	public $breadcrumb=['Home'=>'index','Project'=>'xepan_projects_project','Status'=>'#'];
	
	function page_index(){
		// parent::init();

		$project_id = $this->app->stickyGET('project_id');
		
		$model_project = $this->add('xepan\projects\Model_Formatted_Project');
		
		if($project_id){
			$model_project->load($project_id);
		}

		// $top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		// $top_view->setModel($model_project,['name']);

		$model_employee = $this->add('xepan\projects\Model_Employee');
		$model_employee->addCondition('status','Active');
		$model_employee->setOrder('pending_tasks_count','desc');
		// $model_employee->getElement('pending_tasks_count')->destroy();
		// $model_employee->addExpression('pending_tasks_count')->set(function ($m,$q)use($project_id){
		// 	return $m->refSQL('xepan\projects\Task')
		// 				->addCondition('status','Pending')
		// 				->addCondition('project_id',$project_id)
		// 				->count();
		// });
		
		$project_detail_grid=$this->add('xepan\hr\Grid');
		$project_detail_grid->add('xepan\base\Controller_Avatar',['options'=>['size'=>40,'border'=>['width'=>0]],'name_field'=>'name','default_value'=>'']);
		$project_detail_grid->addPaginator(50);
		$project_detail_grid->addQuickSearch(['name']);
		$project_detail_grid->setModel($model_employee,['name','running_task','project','pending_tasks_count','running_task_since']); 
		
		$project_detail_grid->addHook('formatRow',function($g){
			$g->current_row['running_task_since'] = $this->seconds2human($g->model['running_task_since']);
			$g->current_row_html['pending_tasks_count'] = '<a href="#'.$g->model->id.'" data-id="'.$g->model->id.'" class="do-show-pending-task" >'.$g->model['pending_tasks_count'].'</a>';
		});

		$project_detail_grid->js('click')->_selector('.do-view-project-live')->univ()->frameURL('Employee Project Status',[$this->api->url('xepan_projects_dailyanalysis'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		$project_detail_grid->js('click')->_selector('.do-show-pending-task')->univ()->frameURL('Employee Pending Tasks',[$this->api->url('./employee_pending_tasks'),'employee_id'=>$this->js()->_selectorThis()->data('id')]);

	}

	function page_employee_pending_tasks(){
		$emp_id = $this->app->stickyGET('employee_id');

		$grid = $this->add('xepan\base\Grid');
		
		$tasks = $this->add('xepan\projects\Model_Task');
		$tasks->addCondition('assign_to_id',$emp_id);
		$tasks->addCondition('status',['Pending','Submitted','Assigned','Inprogress']);

		$grid->setModel($tasks,['task_name','description','created_by','assign_to','related','status','type','project','is_recurring']);

		$grid->addHook('formatRow',function($g){
			$g->current_row_html['task_name']=$g->model['task_name']. '<br/> ('. $g->model['type'].')' . ($g->model['is_recurring']?'<br/>[Recurring]':'');
			$g->current_row_html['from_to']=$g->model['created_by']. '<br/> => <br/> '. $g->model['assign_to'];
		});

		$grid->addColumn('from_to');
		$grid->removeColumn('created_by');
		$grid->removeColumn('assign_to');
		$grid->removeColumn('type');
		$grid->removeColumn('is_recurring');

		$grid->addOrder()->move('from_to','before','task_name')->now();

		$grid->addPaginator(50);
	}

	function seconds2human($ss) {
		$s = $ss % 60;
		$m = (floor(($ss%3600)/60)>0)?floor(($ss%3600)/60).' minutes':'';
		$h = (floor(($ss % 86400) / 3600)>0)?floor(($ss % 86400) / 3600).' hours':'';
		$d = (floor(($ss % 2592000) / 86400)>0)?floor(($ss % 2592000) / 86400).' days':'';
		$M = (floor($ss / 2592000)>0)?floor($ss / 2592000).' months':'';
		return "$M $d $h $m $s seconds";
	}

	// function defaultTemplate(){
	// 	return['view\projectlive'];
	// }
}