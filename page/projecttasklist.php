<?php

namespace xepan\projects;

class page_projecttasklist extends \xepan\projects\page_configuration{
	public $title ="Tasks/Requests";
	function init(){
		parent::init();

		$project_id = $this->app->stickyGET('project_id');
		$created_by_id = $this->app->stickyGET('created_by');
		$assigned_to_id = $this->app->stickyGET('assigned_to');
		$task_status = $this->app->stickyGET('task_status');
		$model_task = $this->add('xepan\projects\Model_Task');
		
		$created_by_array = [];	
		$assigned_to_array = [];
		
		if($project_id){						
			$model_task->addCondition('project_id',$project_id);

			foreach ($model_task as $task) { 
				$created_by_array [] = $task['created_by_id'];
				$assigned_to_array [] = $task['assign_to_id'];		
			}	
		}

		$complete_task_list_view = $this->add('xepan\hr\Grid',null,'task_list_view');
	    $complete_task_list_view->setModel($model_task,['task_name','created_by','assign_to','status']);
	    $complete_task_list_view->addPaginator($ipp=25);

	    $complete_task_list_view->addQuickSearch(['task_name']);

	    $created_by_employee_m = $this->add('xepan\hr\Model_Employee');
	    $created_by_employee_m->addCondition('status','Active');
	    
	   	if(!empty($created_by_array))
	    	$created_by_employee_m->addCondition('id',array_unique($created_by_array));

	    $assigned_by_employee_m = $this->add('xepan\hr\Model_Employee');
	    $assigned_by_employee_m->addCondition('status','Active');
	   	
	   	if(!empty($assigned_to_array))
	    	$assigned_by_employee_m->addCondition('id',array_unique($assigned_to_array));

	    $frm = $this->add('Form',null,'form');
	    $frm->setLayout('view/form/project-task-list-form');
		$created_by_field = $frm->addField('Dropdown','created_by')->setEmptyText('Select a employee');
		$created_by_field->setModel($created_by_employee_m);
		$assigned_to_field = $frm->addField('Dropdown','assigned_to')->setEmptyText('Select a employee');
		$assigned_to_field->setModel($assigned_by_employee_m);
		$status = $frm->addField('Dropdown','taskstatus');
		$status->setvalueList(['Pending'=>'Pending','Inprogress'=>'Inprogress','Assigned'=>'Assigned','Submitted'=>'Submitted','Completed'=>'Completed'])->setEmptyText('Select a status');
		$frm->addSubmit('Filter')->addClass('btn btn-primary btn-block');

		if($created_by_id)
				$model_task->addCondition('created_by_id',$_GET['created_by']);

		if($assigned_to_id)
				$model_task->addCondition('assign_to_id',$_GET['assigned_to']);

		if($task_status)
				$model_task->addCondition('status',$_GET['task_status']);

		if($frm->isSubmitted()){
			$complete_task_list_view->js()->reload(
					[
						'created_by'=>$frm['created_by'],
						'assigned_to'=>$frm['assigned_to'],
						'task_status'=>$frm['taskstatus']
						]
					)->execute();
		}
		

	$complete_task_list_view->addHook('formatRow',function($g){
		$g->current_row_html['task_name'] = "<div class='all-task-detail' style= 'cursor:pointer; cursor: hand; max-width:600px;' data-id='". $g->model->id ."'>" .$g->model['task_name']."</div>" ;	
	});
	
	$complete_task_list_view->js('click')->_selector(".all-task-detail")->univ()->frameURL('TASK DETAIL',[$this->app->url('xepan_projects_taskdetail'),'task_id'=>$this->js()->_selectorThis()->data('id'),'project_id'=>$project_id]);
	
	}

	function defaultTemplate(){
		return['view\project-complete-task-list'];
	}
}