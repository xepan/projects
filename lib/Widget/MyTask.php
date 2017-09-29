<?php

namespace xepan\projects;

class Widget_MyTask extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Employee');
		
		$this->grid = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList','grid_options'=>['del_action_wrapper'=>true]]);	    
		$this->grid->addClass('task-assigned-to-me');
	}

	function recursiveRender(){
	   	
	   	if(isset($this->report->employee))
	   		$employee_id = $this->report->employee;
	   	else
	   		$employee_id = $this->app->employee->id;

	    $this->grid->template->trySet('task_view_title','My Tasks');
	    $this->grid->js('reload')->reload();

		if(!$this->grid->isEditing()){
			$this->grid->grid->template->trySet('task_view_title', 'My Tasks');
			$this->grid->grid->template->trySet('title_url',$this->app->url('xepan_projects_mytasks'));
			$this->grid->grid->addPaginator(10);
		}

		$this->grid->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','image_field'=>'created_by_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

		$task_assigned_to_me_model = $this->add('xepan\projects\Model_Formatted_Task');
	    $task_assigned_to_me_model
	    			->addCondition('status',['Pending','Inprogress','Assigned'])
	    			->addCondition(
	    				$task_assigned_to_me_model->dsql()->orExpr()
	    					->where('assign_to_id',$employee_id)
	    					->where(
    								$task_assigned_to_me_model->dsql()->andExpr()
    									->where('created_by_id',$employee_id)
    									->where('assign_to_id',null)
	    							)
	    				)
	    			->addCondition('type','Task');
	   	  
	    $this->grid->setModel($task_assigned_to_me_model)->setOrder('updated_at','desc');			

		return parent::recursiveRender();
	}
}