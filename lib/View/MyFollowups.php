<?php

namespace xepan\projects;

class View_MyFollowups extends \View{
	function init(){
		parent::init();

		$my_followups_model = $this->add('xepan\projects\Model_Task');
	    $my_followups_model->addCondition([['assign_to_id',$this->app->employee->id],['created_by_id',$this->app->employee->id]]);
	    $my_followups_model->addCondition('starting_date','>=',$this->app->today);
	    $my_followups_model->addCondition('type','Followup');

		$my_followups_crud = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList']);
		$my_followups_crud->setModel($my_followups_model);
		$my_followups_crud->grid->template->trySet('task_view_title','My FollowUps');
		$my_followups_crud->grid->addPaginator(10);
	}
}