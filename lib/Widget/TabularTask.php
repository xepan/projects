<?php

namespace xepan\projects;

class Widget_TabularTask extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Employee');
		
		/********************************************
			TABS FOR TASKS
		*********************************************/
		$tabs = $this->add('Tabs');
		$assigned_to_me = $tabs->addTab('Assigned To');
		$assigned_by_me= $tabs->addTab('Assigned By');
		$waiting_for_approval = $tabs->addTab('Submitted');
		$task_comments_tab = $tabs->addTab('Comments');

		/**************************************************
			TASK VIEWS
		***************************************************/
		$this->assigned_to_me_grid = $assigned_to_me->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList','grid_options'=>['del_action_wrapper'=>true]]);	    
		$this->assigned_by_me_grid = $assigned_by_me->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList','grid_options'=>['del_action_wrapper'=>true]]);	    
		$this->waiting_for_approval_grid = $waiting_for_approval->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList','grid_options'=>['del_action_wrapper'=>true]]);	    
		$this->task_comments = $task_comments_tab->add('xepan\hr\Grid');	    
	}

	function recursiveRender(){

		/*********************************************
			MODELS FOR TASK
		**********************************************/
		$task_assigned_to_me_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('type','Task')
										  ->setOrder(['updated_at','last_comment_time','priority']);
	    			
	    $task_assigned_by_me_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('type','Task')
	                                      ->setOrder(['updated_at','last_comment_time']);
	    
	    $task_waiting_for_approval_model = $this->add('xepan\projects\Model_Formatted_Task')
										  		->addCondition('type','Task')
	    										->setOrder(['updated_at','last_comment_time']);
	
		$model_comment = $this->add('xepan\projects\Model_Comment');

		/*******************************************************
			ADDING DATE TIME AND EMPLOYEE CONDITIONS ON MODEL
		********************************************************/
		
		if(isset($this->report->start_date)){
			$model_comment->addCondition('created_at','>',$this->report->start_date);
			$model_comment->addCondition('created_at','<',$this->app->nextDate($this->report->end_date));
			
			$task_assigned_to_me_model->addCondition('starting_date','>',$this->report->start_date);
			$task_assigned_to_me_model->addCondition('starting_date','<',$this->app->nextDate($this->report->end_date));

			$task_assigned_by_me_model->addCondition('starting_date','>',$this->report->start_date);
			$task_assigned_by_me_model->addCondition('starting_date','<',$this->app->nextDate($this->report->end_date));

			$task_waiting_for_approval_model->addCondition('starting_date','>',$this->report->start_date);
			$task_waiting_for_approval_model->addCondition('starting_date','<',$this->app->nextDate($this->report->end_date));
		}
		
		if(isset($this->report->employee)){	
			$task_assigned_to_me_model->addCondition($task_assigned_to_me_model->dsql()->orExpr()
	    							  ->where('assign_to_id',$this->report->employee)
	    							  ->where($task_assigned_to_me_model->dsql()->andExpr()
								      ->where('created_by_id',$this->report->employee)
									  ->where('assign_to_id',null)));

			$task_assigned_by_me_model->addCondition('created_by_id',$this->report->employee)
  									  ->addCondition('assign_to_id','<>',$this->report->employee)
									  ->addCondition('assign_to_id','<>',null)
									  ->addCondition('status','<>','Submitted');

			$task_waiting_for_approval_model->addCondition('status','Submitted')
											->addCondition('assign_to_id','<>',null)
											->addCondition( $this->app->db->dsql()->orExpr()
											->where('created_by_id',$this->report->employee)
		  									->where('assign_to_id',$this->report->employee));
			$model_comment->addCondition('employee_id',$this->report->employee);
		
		}


		/***********************************************
			SETTING MODEL ON VIEWS 
		************************************************/
		$this->assigned_to_me_grid->setModel($task_assigned_to_me_model);  
		$this->assigned_by_me_grid->setModel($task_assigned_by_me_model);
		$this->waiting_for_approval_grid->setModel($task_waiting_for_approval_model);
		$this->task_comments->setModel($model_comment,['comment','task','created_at']);

		// /***********************************************
		// 	ADDING PAGINATOR ON VIEWS 
		// ************************************************/
		// if(!$this->assigned_to_me_grid->isEditing())
		// 	$this->assigned_to_me_grid->addPaginator(10);
		
		// if(!$this->assigned_by_me_grid->isEditing())
		// $this->assigned_by_me_grid->addPaginator('10');
		
		// if(!$this->waiting_for_approval_grid->isEditing())
		// $this->waiting_for_approval_grid->addPaginator('10');
		
		// if(!$this->task_comments->isEditing())
		// $this->task_comments->addPaginator('10');

		/***********************************************
			SETTING TITLE ON VIEWS 
		************************************************/ 
	  	$this->assigned_to_me_grid->template->trySet('task_view_title','Assigend To Employee');  
		$this->assigned_by_me_grid->template->trySet('task_view_title','Assigned By Employee');
		$this->waiting_for_approval_grid->template->trySet('task_view_title','Submitted Tasks');
		$this->task_comments->template->trySet('task_view_title','Task Comments');

		/***********************************************
			SETTING AVATAR ON VIEWS 
		************************************************/
		$this->assigned_to_me_grid->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','image_field'=>'created_by_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$this->assigned_by_me_grid->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assigned_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$this->waiting_for_approval_grid->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assigned_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

		return parent::recursiveRender();
	}
}