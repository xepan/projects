<?php

namespace xepan\projects;

class page_projectdetail extends \xepan\projects\page_sidemenu{
	public $title = "Project Detail";
	public $breadcrumb=['Home'=>'index','Project'=>'xepan_projects_project','Detail'=>'#'];

	function init(){
		parent::init();

		$this->js(true)->_load('timer.jquery');

        $from_date = $this->app->stickyGET('from_date');			   
        $to_date = $this->app->stickyGET('to_date');			   
        $task_priority = $this->app->stickyGET('priority');			   
		$task_id = $this->app->stickyGET('task_id');
		$search = $this->app->stickyGET('search');
		$project_id = $this->app->stickyGET('project_id');
		
		if(!$project_id) return;
		
		$model_project = $this->add('xepan\projects\Model_Formatted_Project')->load($project_id);
		
		/***************************************************************************
			Adding views
		***************************************************************************/
		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->template->tryDel('my-timesheet-button-wrapper');
		$top_view->setModel($model_project);

		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('project_id',$project_id);
		$employee = $this->add('xepan\hr\Model_Employee');

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

		$task_assigned_to_me->grid->addPaginator(25);
		$task_assigned_by_me->grid->addPaginator(25);
		$task_waiting_for_approval->grid->addPaginator(25);


		$filter_form = $this->add('Form',null,'filter_form');
	    $filter_form->setLayout('view\form\task-list-filter-form');
		$filter_form->addField('DatePicker','from_date')->set($this->app->now);
		$filter_form->addField('DatePicker','to_date')->set($this->app->now);		
		$filter_form->addField('Dropdown','priority')->setvalueList(['25'=>'Low','50'=>'Medium','75'=>'High','90'=>'Critical']);
	    $filter_form->addSubmit('ApplyFilter')->addClass('btn btn-primary btn-block');

		$frm = $task_assigned_to_me->grid->addQuickSearch(['task_name']);
		if(!$frm->recall('task_status',false)) $frm->memorize('task_status',['Pending','Inprogress','Assigned']);
		$status = $frm->addField('Dropdown','task_status');
		$status->setvalueList(['Pending'=>'Pending','Inprogress'=>'Inprogress','Assigned'=>'Assigned','Submitted'=>'Submitted','Completed'=>'Completed'])->setEmptyText('Select a status');
		$status->setAttr(['multiple'=>'multiple']);

		$frm1 = $task_assigned_by_me->grid->addQuickSearch(['task_name']);
		if(!$frm1->recall('task_status',false)) $frm1->memorize('task_status',['Pending','Inprogress','Assigned']);
		$status1 = $frm1->addField('Dropdown','task_status');
		$status1->setvalueList(['Pending'=>'Pending','Inprogress'=>'Inprogress','Assigned'=>'Assigned','Submitted'=>'Submitted','Completed'=>'Completed'])->setEmptyText('Select a status');
		$status1->setAttr(['multiple'=>'multiple']);
		
		$frm2 = $task_waiting_for_approval->grid->addQuickSearch(['task_name']);
				
		$frm->addHook('applyFilter',function($f,$m){
			if(!is_array($f['task_status'])) $f['task_status'] = explode(',',$f['task_status']);
			if($f['task_status'] AND $m instanceOf \xepan\projects\Model_Task){
				$m->addCondition('status',$f['task_status']);
				$f->memorize('task_status',$f['task_status']);
			}else{
				$f->forget('task_status');
			}
		});

		$frm1->addHook('applyFilter',function($f,$m){
			if(!is_array($f['task_status'])) $f['task_status'] = explode(',',$f['task_status']);
			if($f['task_status'] AND $m instanceOf \xepan\projects\Model_Task){
				$m->addCondition('status',$f['task_status']);
				$f->memorize('task_status',$f['task_status']);
			}else{
				$f->forget('task_status');
			}
		});
		

		$status->js('change',$frm->js()->submit());
		$status1->js('change',$frm1->js()->submit());



	    $task_assigned_to_me->template->trySet('task_view_title','Assigned To Me');
	    $task_assigned_by_me->template->trySet('task_view_title','Assigned By Me');
		$task_waiting_for_approval->template->trySet('task_view_title','Submitted To Me');

		$task_assigned_to_me->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','image_field'=>'created_by_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_assigned_by_me->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assigned_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_waiting_for_approval->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assigned_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
	    
		$status = 'Completed';

	    $task_assigned_to_me_model = $this->add('xepan\projects\Model_Formatted_Task');
	    $task_assigned_to_me_model
	    			->addCondition('project_id',$project_id)
	    			->addCondition(
	    				$task_assigned_to_me_model->dsql()->orExpr()
	    					->where('assign_to_id',$this->app->employee->id)
	    					->where(
    								$task_assigned_to_me_model->dsql()->andExpr()
    									->where('created_by_id',$this->app->employee->id)
    									->where('assign_to_id',null)
	    							)
	    				)
	    			->addCondition('type','Task');

	    $task_assigned_to_me_model->setOrder(['updated_at','last_comment_time','priority']);			
	    			
	    $task_assigned_by_me_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('project_id',$project_id)
										  ->addCondition('created_by_id',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',null)
										  ->addCondition('status','<>','Submitted')
	    								  ->addCondition('type','Task');	

	    $task_assigned_by_me_model->setOrder(['updated_at','last_comment_time']);
	    
	    $task_waiting_for_approval_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('status','Submitted')
										  ->addCondition('assign_to_id','<>',null)
										  ->addCondition('project_id',$project_id)
										  ->addCondition( 
										  	$this->app->db->dsql()->orExpr()
												->where('created_by_id',$this->app->employee->id)
			  									->where('assign_to_id',$this->app->employee->id))	
										  ->addCondition('type','Task');			
		$task_waiting_for_approval_model->setOrder(['updated_at','last_comment_time']);
		
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

		$task_assigned_to_me->setModel($task_assigned_to_me_model);		
		$task_assigned_by_me->setModel($task_assigned_by_me_model);
		$task_waiting_for_approval->setModel($task_waiting_for_approval_model);

		if($filter_form->isSubmitted()){
        	$js = [ $task_assigned_by_me->js()->reload(['from_date'=>$filter_form['from_date'],'to_date'=>$filter_form['to_date'],'priority'=>$filter_form['priority']]),
        			$task_assigned_to_me->js()->reload(['from_date'=>$filter_form['from_date'],'to_date'=>$filter_form['to_date'],'priority'=>$filter_form['priority']])
        		  ];

			$filter_form->js(null,$js)->execute();	
		}

		if($task_id){
			$task->load($task_id);			
		}
		$task_assigned_to_me_url = $this->api->url(null,['cut_object'=>$task_assigned_to_me->name]);

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

		/***************************************************************************
			Js to show task detail view etc.
		***************************************************************************/
		
		$top_view->js('click',$this->js()->univ()->frameURL("ADD NEW TASK/Request",$this->api->url($vp->getURL())))->_selector('.add-task');
		
	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}
}