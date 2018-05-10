<?php

namespace xepan\projects;

class page_mytasks extends \xepan\base\Page{
	public $title = "My Tasks/Requests";
	
	function init(){
		parent::init();		

		$from_date = $this->app->stickyGET('from_date');			   
        $to_date = $this->app->stickyGET('to_date');			   
        $task_priority = $this->app->stickyGET('priority');			 

		$model_project = $this->add('xepan\projects\Model_Formatted_Project');
		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project);

		$top_view->template->tryDel('progress_bar_wrapper');
		$top_view->template->tryDel('name_wrapper');

		$task_assigned_to_me = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'leftview');	    
	    $task_assigned_to_me->grid->addClass('task-assigned-to-me');
	    $task_assigned_to_me->js('reload')->reload();
	    
	    $task_assigned_by_me = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'middleview');	    
	    $task_assigned_by_me->addClass('task-assigned-by-me');
	    $task_assigned_by_me->js('reload')->reload();
	    
	    $task_waiting_for_approval = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'rightview');	    
	    $task_waiting_for_approval->grid->addClass('task-waiting-for-approval');
	    $task_waiting_for_approval->js('reload')->reload();

	    $task_assigned_to_me->grid->template->trySet('task_view_title','Assigned To Me');
	    $task_assigned_by_me->grid->template->trySet('task_view_title','Assigned By Me');
	    $task_waiting_for_approval->grid->template->trySet('task_view_title','Waiting For Approval');

	    if(!$task_assigned_to_me->isEditing())
			$task_assigned_to_me->grid->addPaginator(25);
	    if(!$task_assigned_by_me->isEditing())
			$task_assigned_by_me->grid->addPaginator(25);
	    if(!$task_waiting_for_approval->isEditing())
			$task_waiting_for_approval->grid->addPaginator(25);

		$filter_form = $this->add('Form',null,'filter_form');
		$filter_form->add('xepan\base\Controller_FLC')
		->showLables(true)
		->makePanelsCoppalsible(true)
		->layout([
				'from_date'=>'Filter~c1~3~closed',
				'to_date'=>'c2~3',
				'priority'=>'c3~3',
				'FormButtons~'=>'c4~3'
			]);

	    // $filter_form->setLayout('view\form\task-list-filter-form');
		$filter_form->addField('DatePicker','from_date')->set($this->app->now);
		$filter_form->addField('DatePicker','to_date')->set($this->app->now);		
		$filter_form->addField('Dropdown','priority')->setvalueList([0=>'Any','25'=>'Low','50'=>'Medium','75'=>'High','90'=>'Critical']);
	    $filter_form->addSubmit('ApplyFilter')->addClass('btn btn-primary btn-block');

		$status_array = [];	
		$status_array = [	'Pending'=>'Pending',
							'Inprogress'=>'Inprogress',
							'Assigned'=>'Assigned',
							'Submitted'=>'Submitted',
							'Completed'=>'Completed'
						];	

		$frm = $task_assigned_to_me->grid->addQuickSearch(['task_name']);
		$frm->add('xepan\base\Controller_FLC')
		->showLables(true)
		->makePanelsCoppalsible(true)
		->layout([
				'task_status'=>'Filter (To Me)~c1~12~closed',
				'project'=>'c3~12'
			]);
		if(!$frm->recall('task_status',false)) $frm->memorize('task_status',['Pending','Inprogress','Assigned']);
		$status = $frm->addField('Dropdown','task_status');
		$status->setvalueList(['Pending'=>'Pending','Inprogress'=>'Inprogress','Assigned'=>'Assigned','Submitted'=>'Submitted','Completed'=>'Completed'])->setEmptyText('Select a status');
		$status->setAttr(['multiple'=>'multiple']);
		$status->setValueList($status_array);

		$project_field = $frm->addField('Dropdown','project')->setEmptyText('Select a Project');
		$project_field->setModel('xepan\projects\Project');
	
		$frm1 = $task_assigned_by_me->grid->addQuickSearch(['task_name']);
		$frm1->add('xepan\base\Controller_FLC')
		->showLables(true)
		->makePanelsCoppalsible(true)
		->layout([
				'task_status'=>'Filter (By Me)~c1~12~closed',
				'project'=>'c3~12'
			]);
		if(!$frm1->recall('task_status',false)) $frm1->memorize('task_status',['Pending','Inprogress','Assigned']);
		$frm2 = $task_waiting_for_approval->grid->addQuickSearch(['task_name']);
		$frm2->add('xepan\base\Controller_FLC')
		->showLables(true)
		->makePanelsCoppalsible(true)
		->layout([
				'project'=>'Filter (To Approve)~c1~12~closed',
			]);
		$status1 = $frm1->addField('Dropdown','task_status');
		$status1->setAttr(['multiple'=>'multiple']);
		$status1->setValueList($status_array);

		$project_field1 = $frm1->addField('Dropdown','project')->setEmptyText('Select a Project');
		$project_field1->setModel('xepan\projects\Project');

		$project_field2 = $frm2->addField('Dropdown','project')->setEmptyText('Select a Project');
		$project_field2->setModel('xepan\projects\Project');

		$frm->addHook('applyFilter',function($f,$m){
			if(!is_array($f['task_status'])) $f['task_status'] = explode(',',$f['task_status']);
			
			if($f['task_status'] AND $m instanceOf \xepan\projects\Model_Task){
				$m->addCondition('status',$f['task_status']);
				$f->memorize('task_status',$f['task_status']);
			}else{
				$f->forget('task_status');
			}

			if($f['project'] AND $m instanceOf \xepan\projects\Model_Project){
				$m->addCondition('project_id',$f['project']);
			}
		});

		$frm1->addHook('applyFilter',function($f,$m){
			if(!is_array($f['task_status'])) $f['task_status'] = explode(',',$f['task_status']);

			if($f['task_status'] AND $m instanceOf \xepan\projects\Model_Task){				
				$m->addCondition('status',$f['task_status']);
				$f->memorize('task_status',$f['task_status']);
			}

			if($f['project'] AND $m instanceOf \xepan\projects\Model_Project){
				$m->addCondition('project_id',$f['project']);
			}
		});

		$frm2->addHook('applyFilter',function($f,$m){
			if($f['project'] AND $m instanceOf \xepan\projects\Model_Project){
				$m->addCondition('project_id',$f['project']);
			}
		});
		
		$status->js('change',$frm->js()->submit());
		$status1->js('change',$frm1->js()->submit());

		$project_field->js('change',$frm->js()->submit());
		$project_field1->js('change',$frm1->js()->submit());
		$project_field2->js('change',$frm2->js()->submit());

	    $task_assigned_to_me->template->trySet('task_view_title','Assigned To Me');
	    $task_assigned_by_me->template->trySet('task_view_title','Assigned By Me');
		$task_waiting_for_approval->template->trySet('task_view_title','Submitted To Me');

		$task_assigned_to_me->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','image_field'=>'created_by_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_assigned_by_me->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assigned_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_waiting_for_approval->add('xepan\base\Controller_Avatar',['name_field'=>'approval_to','image_field'=>'approval_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

		$status = 'Completed';

	    $task_assigned_to_me_model = $this->add('xepan\projects\Model_Formatted_Task')
	    	->addCondition('type','Task')
	    	->addCondition('is_regular_work',false)
	    	;
	    $field_to_destroy = ['total_duration','is_started','is_running','follower_count','total_comment'/*,'created_by_image'*//*,'assigned_to_image'*/,'related_name','priority_name','assign_employee_status','created_by_employee_status','contact_name','contact_organization'];
	    foreach ($field_to_destroy as $field) {
		    $task_assigned_to_me_model->getElement($field)->destroy();
	    }

	    $task_assigned_to_me_model
	    			->addCondition(
	    				$task_assigned_to_me_model->dsql()->orExpr()
	    					->where('assign_to_id',$this->app->employee->id)
	    					->where(
    								$task_assigned_to_me_model->dsql()->andExpr()
    									->where('created_by_id',$this->app->employee->id)
    									->where('assign_to_id',null)
	    							)
	    				)->addCondition('type','Task');
	    $task_assigned_to_me_model->setOrder(['updated_at','last_comment_time','priority']);
	    			
	    $task_assigned_by_me_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('created_by_id',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',null)
										  ->addCondition('status','<>','Submitted')
										  ->addCondition('is_regular_work',false)
										  ->addCondition('type','Task');
		// $task_assigned_by_me_model->getElement('assign_to_id')->sortable(true);								  
	    $task_assigned_by_me_model->setOrder(['updated_at','last_comment_time']);
	    
	    foreach ($field_to_destroy as $field) {
		    $task_assigned_by_me_model->getElement($field)->destroy();
	    }


	    $task_waiting_for_approval_model = $this->add('xepan\projects\Model_Formatted_Task');
	    
	    // to show correct avatar - If I have assigned show created_by (Who is about to approve my submittion) or if someone else has submitted show asigned_to (Who submitted)
	    $task_waiting_for_approval_model->addExpression('approval_to')->set(function($m,$q){
	    	return $q->expr('IF([created_by_id]=[employee_id],[assigned_to],[created_by])',[
	    		'created_by_id'=>$m->getElement('created_by_id'),
	    		'employee_id'=>$m->app->employee->id,
	    		'assigned_to'=>$m->getElement('assign_to'),
	    		'created_by'=>$m->getElement('created_by')
	    	]);
	    });
	    // to show correct avatar - If I have assigned show created_by (Who is about to approve my submittion) or if someone else has submitted show asigned_to (Who submitted)
	    $task_waiting_for_approval_model->addExpression('approval_to_image')->set(function($m,$q){
	    	return $q->expr('IF([created_by_id]=[employee_id],[assigned_to],[created_by])',[
	    		'created_by_id'=>$m->getElement('created_by_id'),
	    		'employee_id'=>$m->app->employee->id,
	    		'assigned_to'=>$m->getElement('assigned_to_image'),
	    		'created_by'=>$m->getElement('created_by_image')
	    	]);
	    });
		$task_waiting_for_approval_model->addCondition('status','Submitted')
										  ->addCondition('assign_to_id','<>',null)
										  ->addCondition( 
										  	$this->app->db->dsql()->orExpr()
												->where('created_by_id',$this->app->employee->id)
			  									->where('assign_to_id',$this->app->employee->id));	
	    $task_waiting_for_approval_model->setOrder(['updated_at','last_comment_time']);
		
		foreach ($field_to_destroy as $field) {
		    $task_waiting_for_approval_model->getElement($field)->destroy();
	    }

		if($from_date){			
			$task_assigned_to_me_model->addCondition('starting_date','>=',$from_date);
			$task_assigned_by_me_model->addCondition('starting_date','>=',$from_date);
		}

		if($to_date){			
			$task_assigned_by_me_model->addCondition('deadline','<=',$this->app->nextDate($to_date));
			$task_assigned_to_me_model->addCondition('deadline','<=',$this->app->nextDate($to_date));
		}

		if($task_priority){			
			$task_assigned_by_me_model->addCondition('priority',$task_priority);		
			$task_assigned_to_me_model->addCondition('priority',$task_priority);		
		}

		$task_assigned_to_me->setModel($task_assigned_to_me_model)->setOrder('updated_at','desc');
		$task_assigned_by_me->setModel($task_assigned_by_me_model)->setOrder('updated_at','desc');
		$task_waiting_for_approval->setModel($task_waiting_for_approval_model)->setOrder('updated_at','desc');	
		
		if($filter_form->isSubmitted()){
        	$js = [ $task_assigned_by_me->js()->reload(['from_date'=>$filter_form['from_date'],'to_date'=>$filter_form['to_date'],'priority'=>$filter_form['priority']]),
        			$task_assigned_to_me->js()->reload(['from_date'=>$filter_form['from_date'],'to_date'=>$filter_form['to_date'],'priority'=>$filter_form['priority']])
        		  ];

			$filter_form->js(null,$js)->execute();	
		}
			
		/***************************************************************************
			Virtual page for TASK DETAIL
		***************************************************************************/
		$self = $this;
		$self_url = $this->app->url(null,['cut_object'=>$this->name]);

		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$task_id = $this->app->stickyGET('task_id')?:0;
			$project_id = $this->app->stickyGET('project_id');

			$p->add('xepan\projects\View_Detail',['task_id'=>$task_id,'project_id'=>$project_id]);
		});	

		$vp1 = $this->add('VirtualPage');
		$vp1->set(function($p){
			$tabs = $p->add('Tabs');

			$tab1 = $tabs->addTab('Assigned To Me');
			$task = $p->add('xepan\projects\Model_Task');
			$task->addCondition('type','Task');
			$task->addCondition('is_recurring',true);
			$task->addCondition('assign_to_id',$this->app->employee->id);
			$view = $tab1->add('xepan\projects\View_TaskList');
			$view->setModel($task);
			$view->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
			
			$tab2 = $tabs->addTab('Assigned By Me');
			$task1 = $p->add('xepan\projects\Model_Task');
			$task1->addCondition('type','Task');
			$task1->addCondition('is_recurring',true);
			$task1->addCondition('created_by_id',$this->app->employee->id);
			$view1 = $tab2->add('xepan\projects\View_TaskList');
			$view1->setModel($task1);
			$view1->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		
			$tab3 = $tabs->addTab('Recurring Reminders');
			$task2 = $p->add('xepan\projects\Model_Task');
			$task2->addCondition('is_recurring',true);
			$task2->addCondition('type','Reminder');
			$task2->addCondition('created_by_id',$this->app->employee->id);
			$view2 = $tab3->add('xepan\projects\View_TaskList');
			$view2->setModel($task2);
			$view2->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		});	

		/***************************************************************************
			Js to show task detail view etc.
		***************************************************************************/
		$top_view->js('click',$this->js()->univ()->frameURL("ADD NEW TASK/REQUEST",$this->api->url($vp->getURL())))->_selector('.add-task');
		$top_view->js('click',$this->js()->univ()->frameURL("RECURRING TASKS/REQUESTS",$this->api->url($vp1->getURL())))->_selector('.show-recurring-task');
	}

	function defaultTemplate(){
		return ['page\mytask'];
	}
}