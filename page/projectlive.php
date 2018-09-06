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


		$model_employee = $this->add('xepan\projects\Model_Employee');
		$model_employee->addCondition('status','Active');
		$model_employee->setOrder('pending_tasks_count','desc');

		$model_employee->addExpression('total_score')->set(function ($m,$q){
			return $m->add('xepan\base\Model_PointSystem')
						->addCondition('contact_id',$m->getElement('id'))
						->addCondition('timesheet_id','<>',0)
						->sum('score');
		})->sortable(true);

		$post_m = $this->add('xepan\hr\Model_Post');
		$post_m->load($this->app->employee['post_id']);

		switch ($post_m['permission_level']) {
			
			case 'Sibling':
				$this->add('View')->set($this->app->employee['post'].' Post is defined to see Sibling followups and you are seeing everyones followups who are on same post as you are');
				$model_employee->addCondition('post_id',$this->app->employee['post_id']);

				break;
			case 'Department':
				$this->add('View')->set($this->app->employee['post'].' Post is defined to see Department followups and you are seeing everyones followups who are  in same department as you are');
	    		$model_employee->addCondition('department_id',$this->app->employee['department_id']);
				break;
			case 'Global':				
				break;
			default: //SELF
				$model_employee->addCondition('id',$this->app->employee->id);
				break;
		}
		
		$project_detail_grid=$this->add('xepan\hr\Grid',['pass_acl'=>false]);
		$project_detail_grid->add('xepan\base\Controller_Avatar',['options'=>['size'=>40,'border'=>['width'=>0]],'name_field'=>'name','default_value'=>'']);
		$project_detail_grid->addPaginator(50);
		$project_detail_grid->addQuickSearch(['name']);
		$project_detail_grid->setModel($model_employee,['name','running_task','project','total_score','pending_tasks_count','running_task_since','last_geolocation_update','last_location']); 
		$project_detail_grid->addColumn('last_location_detail');
		$project_detail_grid->removeAttachment();
		$project_detail_grid->removeColumn('last_geolocation_update');
		$project_detail_grid->removeColumn('last_location');
		
		$project_detail_grid->addHook('formatRow',function($g){
			if(!$g->model['last_geolocation_update']){
				$g->current_row_html['last_location_detail']="<a href='#map' class='do-show-location' data-id='".$g->model->id."' > -- </a>";
			}else{
				$g->current_row_html['last_location_detail'] = "<a href='#map' class='do-show-location' data-id='".$g->model->id."' >".$this->seconds2human($this->app->my_date_diff($g->model['last_geolocation_update'],$this->app->now)['seconds_total'] ).'<br/>'.$g->model['last_location'].'</a>';
			}

			$g->current_row['running_task_since'] = $this->seconds2human($g->model['running_task_since']);
			$g->current_row_html['pending_tasks_count'] = '<a href="#'.$g->model->id.'" data-id="'.$g->model->id.'" class="do-show-pending-task" >'.$g->model['pending_tasks_count'].'</a>';
			$g->current_row_html['running_task'] = '<a href="#'.$g->model['running_task_id'].'" data-id="'.$g->model->id.'" data-running_task_id="'.$g->model['running_task_id'].'" class="do-show-timesheet" >'.($g->model['running_task']?:' --- ').'</a>';
			$g->current_row_html['total_score'] = '<a href="#'.$g->model['running_task_id'].'" data-id="'.$g->model->id.'" data-running_task_id="'.$g->model['running_task_id'].'" class="do-show-score" >'.($g->model['total_score']?:' --- ').'</a>';
		});

		$project_detail_grid->addOrder()->move('last_location_detail','after','running_task_since')->now();

		$project_detail_grid->js('click')->_selector('.do-show-timesheet')->univ()->frameURL('Employee\'s Today\'s TimeSheet',[$this->api->url('./employeetimesheet'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		$project_detail_grid->js('click')->_selector('.do-view-project-live')->univ()->frameURL('Employee Project Status',[$this->api->url('xepan_projects_dailyanalysis'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		$project_detail_grid->js('click')->_selector('.do-show-pending-task')->univ()->frameURL('Employee Pending Tasks',[$this->api->url('./employee_pending_tasks'),'employee_id'=>$this->js()->_selectorThis()->data('id')]);
		$project_detail_grid->js('click')->_selector('.do-show-score')->univ()->frameURL('Employee Scores',[$this->api->url('./employee_scores'),'employee_id'=>$this->js()->_selectorThis()->data('id')]);
		$project_detail_grid->js('click')->_selector('.do-show-location')->univ()->frameURL('Employee Current Location',[$this->api->url('./employee_location'),'employee_id'=>$this->js()->_selectorThis()->data('id')]);

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
		$timesheet_m->addCondition([['start_date',$for_date],['end_date',$for_date]]);

		// $timesheet_m->getElement('starttime')->type('time');
		// $timesheet_m->getElement('endtime')->type('time');

		$grid = $this->add('Grid');
		$grid->setModel($timesheet_m,['task','starttime','endtime','duration','remark']);
		$grid->addPaginator(50);

		$grid->setFormatter('remark','wrap');

		$grid->addHook('formatRow',function($g){
			$g->current_row_html['starttime'] = date('d-M-Y g:i:s A',strtotime($g->model['starttime']));
			$g->current_row_html['endtime'] = $g->model['endtime']?date('d-M-Y g:i:s A',strtotime($g->model['endtime'])):'';
			$g->current_row['duration'] = $this->seconds2human($g->model['duration']);
		});

		if($form->isSubmitted()){
			$grid->js()->reload(['for_date'=>$form['for_date']])->execute();
		}

	}

	function page_employee_scores(){
		$employee_id = $this->app->stickyGET('employee_id');
		$for_date = $this->app->stickyGET('for_date');
		if(!$for_date) $for_date = $this->app->today;

		$time_wise_seperate = $this->app->stickyGET('time_wise_seperate');


		// filter form

		$form = $this->add('Form');
		$form->add('xepan\base\Controller_FLC')
		->showLables(true)
		->makePanelsCoppalsible(true)
		->layout([
				'for_date'=>'Filter~c1~4~closed',
				'time_wise_seperate'=>'c2~4',
				'FormButtons~'=>'c3~4',
				'activity_count'=>'Actula Activities~c1~12~closed'
			]);
		$form->addField('DatePicker','for_date')->set($for_date);
		$form->addField('Checkbox','time_wise_seperate');
		$form->addSubmit('Filter')->addClass('btn btn-primary');

		// cross checking activity counts

		$grid_comm = $form->layout->add('xepan\base\Grid',['add_sno'=>false],'activity_count');

		$grid_comm->addColumn('communications_with');
		$grid_comm->addColumn('newsletters');
		$grid_comm->addColumn('leads_created');
		$grid_comm->addColumn('followup_closed');
		$grid_comm->addColumn('followup_created');

		$data_array=[[]];
		$data_array[0]['communications_with'] = $this->add('xepan\communication\Model_Communication')
											->addCondition('created_by_id',$employee_id)
											->addCondition('to_id','<>',$employee_id)
											->addCondition('created_at','>=',$for_date)
											->addCondition('created_at','<',$this->app->nextDate($for_date))
											->addCondition('communication_type',['Call','Email','Comment','Sms','Personal'])
											->_dsql()->del('fields')
											->field('COUNT(DISTINCT(to_id))')
											->getOne();

		$data_array[0]['newsletters'] = $this->add('xepan\communication\Model_Communication')
											->addCondition('created_by_id',$employee_id)
											->addCondition('to_id','<>',$employee_id)
											->addCondition('created_at','>=',$for_date)
											->addCondition('created_at','<',$this->app->nextDate($for_date))
											->addCondition('communication_type',['Newsletter'])
											->_dsql()->del('fields')
											->field('COUNT(DISTINCT(to_id))')
											->getOne();

		$data_array[0]['followup_closed'] = $this->add('xepan\projects\Model_FollowUp')
											->addCondition('completed_at','>=',$for_date)
											->addCondition('completed_at','<',$this->app->nextDate($for_date))
											->addCondition('assign_to_id',$employee_id)
											->count()
											->getOne();

		$data_array[0]['followup_created'] = $this->add('xepan\projects\Model_FollowUp')
											->addCondition('created_by_id',$employee_id)
											->addCondition('created_at','>=',$for_date)
											->addCondition('created_at','<',$this->app->nextDate($for_date))
											->count()
											->getOne();

		$data_array[0]['leads_created'] = $this->add('xepan\base\Model_Contact')
											->addCondition('created_by_id',$employee_id)
											->addCondition('created_at','>=',$for_date)
											->addCondition('created_at','<',$this->app->nextDate($for_date))
											->count()
											->getOne();


		$grid_comm->setSource($data_array);
		$grid_comm->removeColumn('id');
		
		$grid_comm->addHook('formatRow',function($g){
			$g->current_row_html['communications_with'] = '<a href="#'.$g->model->id.'" class="do-comm-details">'.$g->model['communications_with'].'</a>';
			$g->current_row_html['newsletters'] = '<a href="#'.$g->model->id.'" class="do-newsletter-details">'.$g->model['newsletters'].'</a>';
			$g->current_row_html['leads_created'] = '<a href="#'.$g->model->id.'" class="do-leadcreate-details">'.$g->model['leads_created'].'</a>';
			$g->current_row_html['followup_closed'] = '<a href="#'.$g->model->id.'" class="do-followupclosed-details">'.$g->model['followup_closed'].'</a>';
			$g->current_row_html['followup_created'] = '<a href="#'.$g->model->id.'" class="do-followupcreated-details">'.$g->model['followup_created'].'</a>';
		});

		$grid_comm->js('click')->_selector('.do-comm-details')->univ()->frameURL('Communication Details',$this->app->url('./comm_detail'));
		$grid_comm->js('click')->_selector('.do-newsletter-details')->univ()->frameURL('Newsletter Details',$this->app->url('./newsletter_detail'));
		$grid_comm->js('click')->_selector('.do-leadcreate-details')->univ()->frameURL('Leads Created Details',$this->app->url('./leadcreate_detail'));
		$grid_comm->js('click')->_selector('.do-followupclosed-details')->univ()->frameURL('FollowUp Closed Details',$this->app->url('./followupclosed_detail'));
		$grid_comm->js('click')->_selector('.do-followupcreated-details')->univ()->frameURL('FollowUp Created Details',$this->app->url('./followupcreated_detail'));

		// points show section
		
		$m= $this->add('xepan\base\Model_PointSystem');
		$m->addCondition('contact_id',$employee_id);

		$m->addExpression('score_per_qty')->set($m->refSQL('rule_option_id')->fieldQuery('score_per_qty'));
		$m->setOrder('created_at desc');

		$score_field='score';
		$qty_field='qty';
		$created_at_field='created_at';
		$remark_field='remarks';

		if(!$time_wise_seperate){
			$qty_field='qty_sum';
			$score_field='score_sum';
			$created_at_field='';
			$remark_field='';

			$m->addExpression('score_sum')->set(function($m,$q)use($employee_id,$for_date){
				return $m->add('xepan\base\Model_PointSystem',['table_alias'=>'score_sum'])
						->addCondition('contact_id',$employee_id)
						->addCondition('created_at_date',$for_date)
						->addCondition('rule_option_id',$m->getElement('rule_option_id'))
						->sum('score')
						->group('rule_option_id')
						;
			});

			$m->addExpression('qty_sum')->set(function($m,$q)use($employee_id,$for_date){
				return $m->add('xepan\base\Model_PointSystem',['table_alias'=>'qty_sum'])
						->addCondition('contact_id',$employee_id)
						->addCondition('created_at_date',$for_date)
						->addCondition('rule_option_id',$m->getElement('rule_option_id'))
						->sum('qty')
						->group('rule_option_id')
						;
			});
			$m->_dsql()->group('rule_option_id');
		}

		
		$edit = ($time_wise_seperate && $this->app->auth->model->isSuperUser());
		$crud = $this->add('xepan\base\CRUD',['allow_add'=>$edit,'allow_del'=>$edit,'allow_edit'=>$edit]);
		
		if($crud->isEditing('add')){
			$m->addCondition('created_at',$for_date);
			$m->addCondition('created_at_date',$for_date);
			$m->addCondition('timesheet_id',-1);
		}

		if($crud->isEditing('edit')){
			$created_at_field=null;
		}

		if(!$crud->iSediting()){
			$m->addCondition('created_at_date',$for_date);
			$m->addCondition('timesheet_id','<>',0);
		}

		$crud->setModel($m,[$created_at_field,'rule_option_id','score_per_qty',$qty_field,$score_field,$remark_field],[$created_at_field,'rule_option','score_per_qty',$qty_field,$score_field,$remark_field]);
		if($crud->isEditing()){
			$crud->form->getElement('rule_option_id')->getModel()->title_field='name_with_score';
		}

		$crud->grid->addFormatter('rule_option','wrap');
		if($remark_field) $crud->grid->addFormatter('remarks','wrap');

		if($form->isSubmitted()){
			$crud->js(null,$grid_comm->js()->reload(['for_date'=>$form['for_date'],'time_wise_seperate'=>$form['time_wise_seperate']?:0]))->reload(['for_date'=>$form['for_date'],'time_wise_seperate'=>$form['time_wise_seperate']?:0])->execute();
		}
	}

	function page_employee_scores_comm_detail(){
		$for_date = $this->app->stickyGET('for_date');
		if(!$for_date) $for_date=$this->app->today;
		$employee_id = $this->app->stickyGET('employee_id');

		$model = $this->add('xepan\communication\Model_Communication')
				->addCondition('created_by_id',$employee_id)
				->addCondition('to_id','<>',$employee_id)
				->addCondition('created_at','>=',$for_date)
				->addCondition('created_at','<',$this->app->nextDate($for_date))
				->addCondition('communication_type',['Call','Email','Comment','Sms','Personal'])
				;

		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($model,['from','to','title','description','communication_type','status']);
		$grid->addFormatter('description','html');
		$grid->addPaginator(50);
	}

	function page_employee_scores_newsletter_detail(){
		$for_date = $this->app->stickyGET('for_date');
		if(!$for_date) $for_date=$this->app->today;
		$employee_id = $this->app->stickyGET('employee_id');

		$model = $this->add('xepan\communication\Model_Communication')
				->addCondition('created_by_id',$employee_id)
				->addCondition('to_id','<>',$employee_id)
				->addCondition('created_at','>=',$for_date)
				->addCondition('created_at','<',$this->app->nextDate($for_date))
				->addCondition('communication_type',['Newsletter'])
				;

		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($model,['from','to','title','description','communication_type','status']);
		// $grid->addFormatter('description','html');
		$grid->addPaginator(50);
	}

	function page_employee_scores_leadcreate_detail(){
		$for_date = $this->app->stickyGET('for_date');
		if(!$for_date) $for_date=$this->app->today;
		$employee_id = $this->app->stickyGET('employee_id');

		$model = $this->add('xepan\marketing\Model_Lead')
				->addCondition('created_by_id',$employee_id)
				->addCondition('created_at','>=',$for_date)
				->addCondition('created_at','<',$this->app->nextDate($for_date))
				;
		$model->addExpression('followup')->set(function($m,$q){
			return $this->add('xepan\projects\Model_FollowUp')
						->addCondition('related_id',$q->getField('id'))
						->count();
		});

		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($model,['name','organization','city','country','score','followup']);
		// $grid->addFormatter('description','html');
		$grid->addPaginator(50);
	}


	function page_employee_scores_followupclosed_detail(){

		$for_date = $this->app->stickyGET('for_date');
		if(!$for_date) $for_date=$this->app->today;

		$employee_id = $this->app->stickyGET('employee_id');


		$model = $this->add('xepan\projects\Model_FollowUp')
				->addCondition('assign_to_id',$employee_id)
				->addCondition('completed_at','>=',$for_date)
				->addCondition('completed_at','<',$this->app->nextDate($for_date))
				;
		$model->addExpression('next_followup')->set(function($m,$q)use($for_date){
			return $this->add('xepan\projects\Model_FollowUp')
						->addCondition('related_id',$q->getField('related_id'))
						->addCondition('starting_date','>',$for_date)
						->count();
		});

		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($model,['task_name','description','related','next_followup']);
		// $grid->addFormatter('description','html');
		$grid->addPaginator(50);
	}

	function page_employee_scores_followupcreated_detail(){

		$for_date = $this->app->stickyGET('for_date');
		if(!$for_date) $for_date=$this->app->today;

		$employee_id = $this->app->stickyGET('employee_id');


		$model = $this->add('xepan\projects\Model_FollowUp')
				->addCondition('created_by_id',$employee_id)
				->addCondition('created_at','>=',$for_date)
				->addCondition('created_at','<',$this->app->nextDate($for_date))
				;
		

		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($model,['task_name','description','related','assign_to','starting_date']);
		// $grid->addFormatter('description','html');
		$grid->addPaginator(50);
	}

	function page_employee_pending_tasks(){
		$emp_id = $this->app->stickyGET('employee_id');

		$tabs = $this->add('Tabs');
		$odf = $tabs->addTab('OverDue Followups');
		$ucf = $tabs->addTab('UpComing Followups');
		$tsk = $tabs->addTab('Tasks');
		$rmd = $tabs->addTab('Reminders');

		// ====== Overdue followups
		$grid = $odf->add('xepan\base\Grid');
		
		$due_f = $odf->add('xepan\projects\Model_Task');
		$due_f->addCondition('assign_to_id',$emp_id);
		$due_f->addCondition('type','Followup');
		$due_f->addCondition('starting_date','<',$this->app->today);
		$due_f->addCondition('status',['Pending','Submitted','Assigned','Inprogress']);

		$due_f->getElement('starting_date')->caption('Followup Date');
		$grid->setModel($due_f,['starting_date','task_name','description','created_by','assign_to','related','status','type','project','is_recurring']);

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


		// ====== Upcoming followups

		$grid = $ucf->add('xepan\base\Grid');
		
		$upc_m = $ucf->add('xepan\projects\Model_Task');
		$upc_m->addCondition('assign_to_id',$emp_id);
		$upc_m->addCondition('type','Followup');
		$upc_m->addCondition('starting_date','>=',$this->app->today);
		$upc_m->addCondition('status',['Pending','Submitted','Assigned','Inprogress']);

		$upc_m->getElement('starting_date')->caption('Followup Date');
		$grid->setModel($upc_m,['starting_date','task_name','description','created_by','assign_to','related','status','type','project','is_recurring']);

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

		// ====== Tasks followups

		$grid = $tsk->add('xepan\base\Grid');
		
		$tsk_m = $tsk->add('xepan\projects\Model_Task');
		$tsk_m->addCondition('assign_to_id',$emp_id);
		$tsk_m->addCondition('type','Task');
		$tsk_m->addCondition('is_regular_work',false);
		$tsk_m->addCondition('status',['Pending','Submitted','Assigned','Inprogress']);

		$tsk_m->getElement('starting_date')->caption('StartDate');
		$grid->setModel($tsk_m,['starting_date','task_name','description','created_by','assign_to','related','status','type','project','is_recurring']);

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


		// ====== Reminder followups

		$grid = $rmd->add('xepan\base\Grid');
		
		$rmd_m = $rmd->add('xepan\projects\Model_Task');
		$rmd_m->addCondition([['assign_to_id',$emp_id],['created_by_id',$emp_id]]);
		$rmd_m->addCondition('type','Reminder');
		$rmd_m->addCondition('is_regular_work',false);
		$rmd_m->addCondition('status',['Pending','Submitted','Assigned','Inprogress']);

		$rmd_m->getElement('starting_date')->caption('StartDate');
		$grid->setModel($rmd_m,['starting_date','task_name','description','created_by','assign_to','related','status','type','project','is_recurring']);

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

	// <iframe src="http://maps.google.com/maps?q=35.856737, 10.606619&z=15&output=embed" width="360" height="270" frameborder="0" style="border:0"></iframe>
	function page_employee_location(){
		$emp_id = $this->app->stickyGET('employee_id');
		$emp = $this->add('xepan\hr\Model_Employee')->load($emp_id);
		

		if($emp['last_latitude']){
			$view = $this->add('View');

			$view->setElement('iframe');
			$view->setAttr('src','http://maps.google.com/maps?q='.$emp['last_latitude'].', '.$emp['last_longitude'].'&z=19&output=embed');
			$view->setAttr('width','100%');
			$view->setAttr('height','600');
			$view->setAttr('frameborder',"0");
			$view->setAttr('border',"0");
		}
		$this->add('View_Info')->set('http://'.$this->app->current_website_name.'.xavoc.com/?page=xepan_hr_trackgeolocationapi&emp='.$emp->id);
		$this->add('View_Info')->set('https://play.google.com/store/apps/details?id=mobi.stos.gpsmobile');
		$this->add('View_Info')->set('latitude:'.$emp['last_latitude'].' longitude:'.$emp['last_longitude']);
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