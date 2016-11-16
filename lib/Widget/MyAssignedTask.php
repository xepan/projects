<?php

namespace xepan\projects;

class Widget_MyAssignedTask extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		
		$this->grid = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList','grid_options'=>['del_action_wrapper'=>true]]);	    
	}

	function recursiveRender(){
		$this->grid->addClass('task-assigned-by-me');
	    $this->grid->js('reload')->reload();

		if(!$this->grid->isEditing()){
			$this->grid->grid->template->trySet('task_view_title', 'My Assigned Tasks');
			$this->grid->grid->template->trySet('title_url',$this->app->url('xepan_projects_mytasks'));
			$this->grid->grid->addPaginator(10);
		}

		$this->grid->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assigned_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

		$assigned_by_me_model = $this->add('xepan\projects\Model_Formatted_Task');
	    $assigned_by_me_model
	    			->addCondition('status',['Assigned','Pending'])
	    			->addCondition('assign_to_id','<>',$this->app->employee->id)
	    			->addCondition('created_by_id',$this->app->employee->id)
	    			->addCondition('type','Task');
	   
	    if(isset($this->report->start_date))
			$assigned_by_me_model->addCondition('starting_date','>',$this->report->start_date);
		if(isset($this->report->end_date))
			$assigned_by_me_model->addCondition('starting_date','<',$this->app->nextDate($this->report->end_date));			
	  
	    $this->grid->setModel($assigned_by_me_model)->setOrder('updated_at','desc');			

		return parent::recursiveRender();
	}
}