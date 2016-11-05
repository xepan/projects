<?php

namespace xepan\projects;

class page_myfollowups extends \xepan\base\Page{
	public $title = "My FollowUps";
	function init(){
		parent::init();

		$this->start_date = $start_date = $_GET['start_date']?:$this->app->today; 		
		$this->end_date = $end_date = $_GET['end_date']?:$this->app->today;  
        $this->status = $this->app->stickyGET('status');			 
        $this->show_overdue = $show_overdue = $this->app->stickyGET('show_overdue');

        $filter_form = $this->add('Form',null,'filter_form');
        $fld = $filter_form->addField('DateRangePicker','period')
                ->setStartDate($start_date)
                ->setEndDate($end_date)
                ->getFutureDatesSet()
                ->getBackDatesSet(false);
        $filter_form->addField('CheckBox','overdue','Show Overdue Followups');
		$filter_form->addSubmit("Filter")->addClass('btn btn-primary');
		
		if($filter_form->isSubmitted()){			
			$filter_form->app->redirect($this->app->url(null,['show_overdue'=>$filter_form['overdue'],'start_date'=>$fld->getStartDate()?:0,'end_date'=>$fld->getEndDate()?:0]));
		}

		$my_followups = $this->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList'],'view');
		
		$status_array = [];	
		$status_array = [	'Pending'=>'Pending',
							'Inprogress'=>'Inprogress',
							'Assigned'=>'Assigned',
							'Submitted'=>'Submitted',
							'Completed'=>'Completed'
						];	
		
		$frm = $my_followups->grid->addQuickSearch(['task_name']);

		$temp_status = ['Pending','Inprogress','Assigned'];

		$count = 0;
		if(is_array($frm->recall('task_status',false))){
			foreach ($frm->recall('task_status',false) as $value) {
				foreach ($temp_status as $v) {
					if($v == $value)
						$count++;
				}
			}
		}
								
		if((!$frm->recall('task_status',false)) || ($show_overdue AND $count==3)) $frm->memorize('task_status',['Pending','Inprogress','Assigned']);
		$status = $frm->addField('Dropdown','task_status');
		$status->setvalueList(['Pending'=>'Pending','Inprogress'=>'Inprogress','Assigned'=>'Assigned','Submitted'=>'Submitted','Completed'=>'Completed'])->setEmptyText('Select a status');
		$status->setAttr(['multiple'=>'multiple']);
		$status->setValueList($status_array);

		$frm->addHook('applyFilter',function($f,$m){
			if(!is_array($f['task_status'])) $f['task_status'] = explode(',',$f['task_status']);
			
			if($f['task_status'] AND $m instanceOf \xepan\projects\Model_Task){
				$m->addCondition('status',$f['task_status']);
				$f->memorize('task_status',$f['task_status']);
			}else{
				$f->forget('task_status');
			}
		});

		if(!$my_followups->isEditing())
			$my_followups->grid->addPaginator(25);	
		
		$status->js('change',$frm->js()->submit());

		$my_followups_model = $this->add('xepan\projects\Model_Task');
	    $my_followups_model->addCondition([['assign_to_id',$this->app->employee->id],['created_by_id',$this->app->employee->id]]);
		
		if($show_overdue){
			$my_followups_model->addCondition('starting_date','<=',$this->app->nextDate($this->end_date));
			// status
		}else{
			$my_followups_model->addCondition('starting_date','>',$this->start_date);
			$my_followups_model->addCondition('starting_date','<=',$this->app->nextDate($this->end_date));
		}


		$my_followups_model->addCondition('type','Followup');

	    $my_followups_model->setOrder('updated_at','desc');
		$my_followups->setModel($my_followups_model);
		$my_followups->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assign_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
	}

	function defaultTemplate(){
		return ['page\followups'];
	}
}