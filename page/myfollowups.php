<?php

namespace xepan\projects;

class page_myfollowups extends \xepan\base\Page{
	public $title = "My FollowUps";
	function init(){
		parent::init();

		$this->start_date = $start_date = $_GET['start_date']?:$this->app->today; 		
		$this->end_date = $end_date = $_GET['end_date']?:$this->app->today;  
        $this->status = $this->app->stickyGET('status');			 

        $filter_form = $this->add('Form',null,'filter_form');
        $fld = $filter_form->addField('DateRangePicker','period')
                ->setStartDate($start_date)
                ->setEndDate($end_date)
                ->getFutureDatesSet()
                ->getBackDatesSet(false);

		$filter_form->addSubmit("Filter")->addClass('btn btn-primary');
		
		if($filter_form->isSubmitted()){
			$filter_form->app->redirect($this->app->url(null,['start_date'=>$fld->getStartDate()?:0,'end_date'=>$fld->getEndDate()?:0]));
		}

		$my_followups = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'view');
		if(!$my_followups->isEditing())
			$my_followups->grid->addPaginator(25);	
		
		$my_followups_model = $this->add('xepan\projects\Model_Task');
	    $my_followups_model->addCondition([['assign_to_id',$this->app->employee->id],['created_by_id',$this->app->employee->id]]);
		
		$my_followups_model->addCondition('starting_date','>',$this->start_date);
		$my_followups_model->addCondition('starting_date','<=',$this->app->nextDate($this->end_date));

		$my_followups_model->addCondition('type','Followup')
	    				   ->addCondition('status','<>','Completed');

	    $my_followups_model->setOrder('updated_at','desc');
		$my_followups->setModel($my_followups_model);
		$my_followups->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assign_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
	}

	function defaultTemplate(){
		return ['page\followups'];
	}
}