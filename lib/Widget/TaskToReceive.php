<?php

namespace xepan\projects;

class Widget_TaskToReceive extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->view = $this->add('View',null,null,['widget\tasktoreceive']);	    
		
	}

	function recursiveRender(){
		$task_assigned_to_me_model = $this->add('xepan\projects\Model_Formatted_Task',null,null,['widget\tasktoreceive']);	   	
	    $task_assigned_to_me_model
	    			->addCondition('status','Assigned')
	    			->addCondition(
	    				$task_assigned_to_me_model->dsql()->orExpr()
	    					->where('assign_to_id',$this->app->employee->id)
	    					->where(
    								$task_assigned_to_me_model->dsql()->andExpr()
    									->where('created_by_id',$this->app->employee->id)
    									->where('assign_to_id',null)
	    							)
	    				)
	    			->addCondition('type','Task');

	    $this->view->template->trySet('t_count',$task_assigned_to_me_model->count()->getOne());			
	    $this->view->template->trySet('url',$this->app->url('xepan_projects_mytasks'));			
		return parent::recursiveRender();
	}
}