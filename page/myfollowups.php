<?php

namespace xepan\projects;

class page_myfollowups extends \xepan\base\Page{
	public $title = "My FollowUps";
	function init(){
		parent::init();

		$my_followups = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList']);
		if(!$my_followups->isEditing())
			$my_followups->grid->addPaginator(25);	
		
		$my_followups_model = $this->add('xepan\projects\Model_Task');
	    $my_followups_model
	    			->addCondition(
	    				$my_followups_model->dsql()->orExpr()
	    					->where('assign_to_id',$this->app->employee->id)
	    					->where('created_by_id',$this->app->employee->id)
	    				)->addCondition('type','Followup');
	    $my_followups_model->setOrder('updated_at','desc');
		
		$my_followups->setModel($my_followups_model);
		$my_followups->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assign_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
	}
}