<?php

namespace xepan\projects;

class page_mytasks extends \xepan\base\Page{
	public $title = "My Tasks";
	function init(){
		parent::init();

		$model_project = $this->add('xepan\projects\Model_Formatted_Project');
		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project);

		$top_view->template->tryDel('progress_bar_wrapper');

		$task_assigned_to_me = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'leftview');	    
	    $task_assigned_by_me = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'middleview');	    
	    $task_waiting_for_approval = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'rightview');	    

	    $task_assigned_to_me->grid->template->trySet('task_view_title','Assigned To Me');
	    $task_assigned_by_me->grid->template->trySet('task_view_title','Assigned By Me');
	    $task_waiting_for_approval->grid->template->trySet('task_view_title','Waiting For Approval');

	    if(!$task_assigned_to_me->isEditing())
			$task_assigned_to_me->grid->addPaginator(25);
	    if(!$task_assigned_by_me->isEditing())
			$task_assigned_by_me->grid->addPaginator(25);
	    if(!$task_waiting_for_approval->isEditing())
			$task_waiting_for_approval->grid->addPaginator(25);

		$status_array = [];	
		$status_array = [	'Pending'=>'Pending',
							'Inprogress'=>'Inprogress',
							'Assigned'=>'Assigned',
							'Submitted'=>'Submitted',
							'Completed'=>'Completed'
						];	

		$frm = $task_assigned_to_me->grid->addQuickSearch(['task_name']);
		if(!$frm->recall('task_status',false)) $frm->memorize('task_status',['Pending','Inprogress','Assigned']);
		$status = $frm->addField('Dropdown','task_status');
		$status->setvalueList(['Pending'=>'Pending','Inprogress'=>'Inprogress','Assigned'=>'Assigned','Submitted'=>'Submitted','Completed'=>'Completed'])->setEmptyText('Select a status');
		$status->setAttr(['multiple'=>'multiple']);
		$status->setValueList($status_array);

		$project_field = $frm->addField('Dropdown','project')->setEmptyText('Select a Project');
		$project_field->setModel('xepan\projects\Project');
	
		$frm1 = $task_assigned_by_me->grid->addQuickSearch(['task_name']);
		if(!$frm1->recall('task_status',false)) $frm1->memorize('task_status',['Pending','Inprogress','Assigned']);
		$frm2 = $task_waiting_for_approval->grid->addQuickSearch(['task_name']);
		$status1 = $frm1->addField('Dropdown','task_status');
		$status1->setAttr(['multiple'=>'multiple']);
		$status1->setValueList($status_array);

		

		

		// $status1->js(true)->trigger('change');

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

		$task_assigned_to_me->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_assigned_by_me->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_waiting_for_approval->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

		$status = 'Completed';

	    $task_assigned_to_me_model = $this->add('xepan\projects\Model_Formatted_Task');
	    $task_assigned_to_me_model
	    			->addCondition(
	    				$task_assigned_to_me_model->dsql()->orExpr()
	    					->where('assign_to_id',$this->app->employee->id)
	    					->where(
    								$task_assigned_to_me_model->dsql()->andExpr()
    									->where('created_by_id',$this->app->employee->id)
    									->where('assign_to_id',null)
	    							)
	    				);

	    $task_assigned_by_me_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('created_by_id',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',null)
										  ->addCondition('status','<>','Submitted');

	    $task_waiting_for_approval_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('created_by_id',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',null)
										  ->addCondition('status','Submitted');	
		
		$task_assigned_to_me->setModel($task_assigned_to_me_model)->setOrder('updated_at','desc');
		$task_assigned_by_me->setModel($task_assigned_by_me_model)->setOrder('updated_at','desc');
		$task_waiting_for_approval->setModel($task_waiting_for_approval_model)->setOrder('updated_at','desc');	
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
		$top_view->js('click',$this->js()->univ()->frameURL("ADD NEW TASK",$this->api->url($vp->getURL())))->_selector('.add-task');
	}

	function defaultTemplate(){
		return ['page\mytask'];
	}
}