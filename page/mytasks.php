<?php

namespace xepan\projects;

class page_mytasks extends \xepan\base\Page{
	public $title = "My Tasks";
	function init(){
		parent::init();


		$task_assigned_to_me = $this->add('xepan\projects\View_TaskList',null,'leftview');	    
	    $task_assigned_by_me = $this->add('xepan\projects\View_TaskList',null,'middleview');	    
	    $task_waiting_for_approval = $this->add('xepan\projects\View_TaskList',null,'rightview');	    

		$task_assigned_to_me->addPaginator(25);
		$task_assigned_by_me->addPaginator(25);
		$task_waiting_for_approval->addPaginator(25);

	    $task_assigned_to_me->template->trySet('task_view_title','Assigned To Me');
	    $task_assigned_by_me->template->trySet('task_view_title','Assigned By Me');
		$task_waiting_for_approval->template->trySet('task_view_title','Submitted To Me');

		$task_assigned_to_me->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_assigned_by_me->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_waiting_for_approval->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

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
		
		$task_assigned_to_me->setModel($task_assigned_to_me_model);
		$task_assigned_by_me->setModel($task_assigned_by_me_model);
		$task_waiting_for_approval->setModel($task_waiting_for_approval_model);



	
		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$task_id = $this->app->stickyGET('task_id')?:0;
			$project_id = $this->app->stickyGET('project_id');

			$p->add('xepan\projects\View_Detail',['task_id'=>$task_id,'project_id'=>$project_id]);
		});	

		// $task_view->js('click')->_selector('.task-item')->univ()->frameURL('TASK DETAIL',[$this->api->url($vp->getURL()),'task_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
	}

	function defaultTemplate(){
		return ['page\mytask'];
	}
}