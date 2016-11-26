<?php

namespace xepan\projects;

class Widget_OverdueTasks extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->grid = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList','grid_options'=>['del_action_wrapper'=>true]]);	    
		$this->grid->addClass('hot-tasks');
	}

	function recursiveRender(){
	    $this->grid->template->trySet('task_view_title','Overdue Tasks');
	    $this->grid->js('reload')->reload();

		if(!$this->grid->isEditing()){
			$this->grid->grid->template->trySet('task_view_title', 'Overdue Tasks');
			$this->grid->grid->template->trySet('title_url',$this->app->url('xepan_projects_mytasks'));
			$this->grid->grid->addPaginator(10);
		}

		$this->grid->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','image_field'=>'created_by_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

		$task_assigned_to_me_model = $this->add('xepan\projects\Model_Formatted_Task');	   	  
	    $task_assigned_to_me_model
	    			->addCondition('status',['Pending','Inprogress','Assigned'])
	    			->addCondition(
	    				$task_assigned_to_me_model->dsql()->orExpr()
	    					->where('assign_to_id',$this->app->employee->id)
	    					->where(
    								$task_assigned_to_me_model->dsql()->andExpr()
    									->where('created_by_id',$this->app->employee->id)
    									->where('assign_to_id',null)
	    							)
	    				)
	    			->addCondition('deadline','<',$this->app->now)
	    			->addCondition('type','Task');
	    $this->grid->setModel($task_assigned_to_me_model)->setOrder('updated_at','desc');			

		return parent::recursiveRender();
	}
}