<?php

namespace xepan\projects;

class Widget_SubmittedTask extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		
		$this->grid = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList','grid_options'=>['del_action_wrapper'=>true]]);	    
	    $this->grid->addClass('task-waiting-for-approval');
	}

	function recursiveRender(){
	    $this->grid->template->trySet('task_view_title','Submitted Tasks');
	    $this->grid->js('reload')->reload();

		if(!$this->grid->isEditing()){
			$this->grid->grid->template->trySet('task_view_title', 'Submitted Tasks');
			$this->grid->grid->template->trySet('title_url',$this->app->url('xepan_projects_mytasks'));
			$this->grid->grid->addPaginator(10);
		}

		$this->grid->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','image_field'=>'created_by_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

		$submitted_task_model = $this->add('xepan\projects\Model_Formatted_Task');
	    $submitted_task_model
	    			->addCondition('status','Submitted')
	    			->addCondition(
	    				$submitted_task_model->dsql()->orExpr()
	    					->where('assign_to_id',$this->app->employee->id)
	    					->where('created_by_id',$this->app->employee->id))
	    			->addCondition('type','Task');
	   
	 //    if(isset($this->report->start_date))
		// 	$submitted_task_model->addCondition('starting_date','>',$this->report->start_date);
		// if(isset($this->report->end_date))
		// 	$submitted_task_model->addCondition('starting_date','<',$this->app->nextDate($this->report->end_date));			
	  
	    $this->grid->setModel($submitted_task_model)->setOrder('updated_at','desc');			

		return parent::recursiveRender();
	}
}