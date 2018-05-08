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
		$model_employee->addExpression('total_score')->set(function ($m,$q){
			return $m->add('xepan\base\Model_PointSystem')
						->addCondition('contact_id',$m->getElement('id'))
						->addCondition('timesheet_id','>',0)
						->sum('score');
		});
		
		$project_detail_grid=$this->add('xepan\hr\Grid',['pass_acl'=>false]);
		$project_detail_grid->add('xepan\base\Controller_Avatar',['options'=>['size'=>40,'border'=>['width'=>0]],'name_field'=>'name','default_value'=>'']);
		$project_detail_grid->addPaginator(50);
		$project_detail_grid->addQuickSearch(['name']);
		$project_detail_grid->setModel($model_employee,['name','running_task','project','total_score','pending_tasks_count','running_task_since']); 
		$project_detail_grid->removeAttachment();
		
		$project_detail_grid->addHook('formatRow',function($g){
			$g->current_row['running_task_since'] = $this->seconds2human($g->model['running_task_since']);
			$g->current_row_html['pending_tasks_count'] = '<a href="#'.$g->model->id.'" data-id="'.$g->model->id.'" class="do-show-pending-task" >'.$g->model['pending_tasks_count'].'</a>';
			$g->current_row_html['running_task'] = '<a href="#'.$g->model['running_task_id'].'" data-id="'.$g->model->id.'" data-running_task_id="'.$g->model['running_task_id'].'" class="do-show-timesheet" >'.($g->model['running_task']?:' --- ').'</a>';
			$g->current_row_html['total_score'] = '<a href="#'.$g->model['running_task_id'].'" data-id="'.$g->model->id.'" data-running_task_id="'.$g->model['running_task_id'].'" class="do-show-score" >'.($g->model['total_score']?:' --- ').'</a>';
		});

		$project_detail_grid->js('click')->_selector('.do-show-timesheet')->univ()->frameURL('Employee\'s Today\'s TimeSheet',[$this->api->url('./employeetimesheet'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		$project_detail_grid->js('click')->_selector('.do-view-project-live')->univ()->frameURL('Employee Project Status',[$this->api->url('xepan_projects_dailyanalysis'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		$project_detail_grid->js('click')->_selector('.do-show-pending-task')->univ()->frameURL('Employee Pending Tasks',[$this->api->url('./employee_pending_tasks'),'employee_id'=>$this->js()->_selectorThis()->data('id')]);
		$project_detail_grid->js('click')->_selector('.do-show-score')->univ()->frameURL('Employee Scores',[$this->api->url('./employee_scores'),'employee_id'=>$this->js()->_selectorThis()->data('id')]);

	}

	function page_employeetimesheet(){
		$employee_id= $this->app->stickyGET('contact_id');
		$for_date = $this->app->stickyGET('for_date');

		if(!$for_date) $for_date = $this->app->today;

		$form = $this->add('Form');
		$form->add('xepan\base\Controller_FLC')
		->showLables(true)
		->makePanelsCoppalsible(true)
		->layout([
				'for_date'=>'Date Select~c1~6~closed',
				'FormButtons'=>'c2~6'
			]);

		$form->addField('DatePicker','for_date')->set($for_date);
		$form->addSubmit('Update')->addClass('btn btn-primary');

		$timesheet_m = $this->add('xepan\projects\Model_Timesheet');
		$timesheet_m->addCondition('employee_id',$employee_id);
		$timesheet_m->addCondition('starttime','>=',$for_date);
		$timesheet_m->addCondition('endtime','<',$this->app->nextDate($for_date));

		$timesheet_m->getElement('starttime')->type('time');
		$timesheet_m->getElement('endtime')->type('time');

		$grid = $this->add('Grid');
		$grid->setModel($timesheet_m,['task','starttime','endtime','duration','remark']);
		$grid->addPaginator(50);

		$grid->setFormatter('remark','wrap');

		$grid->addHook('formatRow',function($g){
			$g->current_row_html['starttime'] = date('g:i:s A',strtotime($g->model['starttime']));
			$g->current_row_html['endtime'] = date('g:i:s A',strtotime($g->model['endtime']));
			$g->current_row['duration'] = $this->seconds2human($g->model['duration']);
		});

		if($form->isSubmitted()){
			$grid->js()->reload(['for_date'=>$form['for_date']])->execute();
		}

	}

	function page_employee_scores(){
		$employee_id = $this->app->stickyGET('employee_id');
		
		$m= $this->add('xepan\base\Model_PointSystem');
		$m->addCondition('contact_id',$employee_id);
		$m->addCondition('timesheet_id','>',0);
		$m->addExpression('score_per_qty')->set($m->refSQL('rule_option_id')->fieldQuery('score_per_qty'));
		$m->setOrder('created_at desc');
		
		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($m,['created_at','rule_option','score_per_qty','qty','score','remarks']);
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