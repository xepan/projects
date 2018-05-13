<?php

namespace xepan\projects;

class Widget_OverdueTasks extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->report->enableFilterEntity('Employee');

		$this->grid = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList','grid_options'=>['del_action_wrapper'=>true]]);	    
		$this->grid->addClass('hot-tasks');
	}

	function recursiveRender(){	
		$employee_id = '';
		if(isset($this->report->employee))
			$employee_id = $this->report->employee;

	    $this->grid->template->trySet('task_view_title','Overdue Tasks');
	    $this->grid->js('reload')->reload();

		if(!$this->grid->isEditing()){
			$this->grid->grid->template->trySet('task_view_title', 'Overdue Tasks');
			$this->grid->grid->template->trySet('title_url',$this->app->url('xepan_projects_mytasks'));
			$this->grid->grid->addPaginator(10);
		}

		$this->grid->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','image_field'=>'created_by_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

		$task = $this->add('xepan\projects\Model_Formatted_Task');
		$task->addCondition('is_regular_work',false);

		if(isset($this->report->employee)){
		    $task->addCondition('status',['Pending','Inprogress','Assigned'])
		    	 ->addCondition($task->dsql()->orExpr()
		    					     ->where('assign_to_id',$employee_id)
		    					     ->where($task->dsql()->andExpr()
	    									      ->where('created_by_id',$employee_id)
	    									      ->where('assign_to_id',null)));
		}	   	  
			 
		$task->addCondition('deadline','<',$this->app->now);			
		$task->addCondition('status','<>','Completed');			
		$task->addCondition('type','Task');
	    
	    $this->grid->setModel($task)->setOrder('updated_at','desc');			

		return parent::recursiveRender();
	}
}