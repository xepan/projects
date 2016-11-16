<?php

namespace xepan\projects;

class Widget_DepartmentFollowUps extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('employee');
		
		$this->grid = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList','grid_options'=>['del_action_wrapper'=>true]]);	    
	}

	function recursiveRender(){

	    $this->grid->template->trySet('task_view_title','Followups');
	    $this->grid->js('reload')->reload();

		if(!$this->grid->isEditing()){
			$this->grid->grid->template->trySet('task_view_title', 'DepartmentFollowUps');
			$this->grid->grid->addPaginator(10);
		}

		$this->grid->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','image_field'=>'created_by_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

		$followups_model = $this->add('xepan\projects\Model_Formatted_Task');
	    $followups_model->addCondition('status',['Pending','Inprogress'])
	    				->addCondition('type','Followup');

	    $department_employees = $this->add('xepan\hr\Model_Employee')
	    							->addCondition('department_id',$this->app->employee['department_id']);

		if(isset($this->report->employee)){
			$followups_model->addCondition(
				$followups_model->dsql()->orExpr()
					->where('assign_to_id',$this->report->employee)
					->where(
						$followups_model->dsql()->andExpr()
							->where('created_by_id',$this->report->employee)
							->where('assign_to_id',null)
						   )
			);
		}else{
			$followups_model->addCondition(
					$followups_model->dsql()->orExpr()
						->where('assign_to_id','in',$department_employees->fieldQuery('id'))
						->where(
							$followups_model->dsql()->andExpr()
								->where('created_by_id','in',$department_employees->fieldQuery('id'))
								->where('assign_to_id',null)
							   )
				);
		}

	   
	   
	    if(isset($this->report->start_date))
			$followups_model->addCondition('starting_date','>',$this->report->start_date);
		if(isset($this->report->end_date))
			$followups_model->addCondition('starting_date','<',$this->app->nextDate($this->report->end_date));			
	  
	    $this->grid->setModel($followups_model)->setOrder('updated_at','desc');			

		return parent::recursiveRender();
	}
}