<?php

namespace xepan\projects;

class page_projectdetail extends \xepan\projects\page_sidemenu{
	public $title = "Project Detail";
	public $breadcrumb=['Home'=>'index','Project'=>'xepan_projects_project','Detail'=>'#'];

	function init(){
		parent::init();

		$this->js(true)->_load('timer.jquery');

		$project_id = $this->app->stickyGET('project_id');
		if(!$project_id) return;
		
		$task_id = $this->app->stickyGET('task_id');
		$search = $this->app->stickyGET('search');

		$model_project = $this->add('xepan\projects\Model_Formatted_Project')->load($project_id);
		
		/***************************************************************************
			Adding views
		***************************************************************************/
		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project);

		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('project_id',$project_id);
		$employee = $this->add('xepan\hr\Model_Employee');

	    $task_assigned_to_me = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'leftview');	    
	    $task_assigned_by_me = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'middleview');	    
	    $task_waiting_for_approval = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'rightview');	    

	    $task_assigned_to_me->template->trySet('task_view_title','Assigned To Me');
	    $task_assigned_by_me->template->trySet('task_view_title','Assigned By Me');
		$task_waiting_for_approval->template->trySet('task_view_title','Submitted To Me');

		$task_assigned_to_me->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_assigned_by_me->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_waiting_for_approval->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
	    
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
	    				);

	    $task_assigned_by_me_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('project_id',$project_id)
										  ->addCondition('created_by_id',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',null)
										  ->addCondition('status','<>','Submitted');

	    $task_waiting_for_approval_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('project_id',$project_id)
										  ->addCondition('created_by_id',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',null)
										  ->addCondition('status','Submitted');	
		
		$task_assigned_to_me->setModel($task_assigned_to_me_model);
		$task_assigned_by_me->setModel($task_assigned_by_me_model);
		$task_waiting_for_approval->setModel($task_waiting_for_approval_model);

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
		
		$top_view->js('click',$this->js()->univ()->frameURL("ADD NEW TASK",$this->api->url($vp->getURL())))->_selector('.add-task');
		

		/***************************************************************************
		  Timesheet PLAY/STOP
		***************************************************************************/
		$task_assigned_to_me->on('click','.current_task_btn',function($js,$data)use($task_assigned_to_me){
			$task_model = $this->add('xepan\projects\Model_Task');
			$task_model->load($data['id']);

			$model_close_timesheet = $this->add('xepan\projects\Model_Timesheet');

			$model_close_timesheet->addCondition('employee_id',$this->app->employee->id);
			$model_close_timesheet->setOrder('starttime','desc');
			$model_close_timesheet->tryLoadAny();

			if($model_close_timesheet->loaded()){
				if(!$model_close_timesheet['endtime']){
					$model_close_timesheet['endtime'] = $this->app->now;
					$model_close_timesheet->save();
				}
			}

			if($data['action']=='start'){
				$task_model['status'] ='Inprogress';
				$task_model->save();
				$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
					
				$model_timesheet['task_id'] = $data['id'];
				$model_timesheet['employee_id'] = $this->app->employee->id;
				$model_timesheet['starttime'] = $this->app->now;
				$model_timesheet->save();

				return [
						$this->js()->_selector('.current_task_btn')->removeClass('fa-stop')->addClass('fa-play'),
						$this->js()->_selector('.dd3-content')->removeClass('alert alert-info'),
						$js->removeClass('fa-play')->addClass('fa-stop')->data('action','stop'),
						$this->js()->_selector('.dd3-content[data-id='.$data['id'].']')->addClass('alert alert-info'),
					];
			}else{
				$task_model['status'] = 'Pending';
				$task_model->save();
				return $js->removeClass('fa-stop')->addClass('fa-play')->data('action','start');	
			}

		});

	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}
}