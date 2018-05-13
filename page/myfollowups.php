<?php

namespace xepan\projects;

class page_myfollowups extends \xepan\base\Page{
	public $title = "My FollowUps";
	
	function init(){
		parent::init();

		$this->app->stickyGET('start_date');
		$this->app->stickyGET('end_date');
		$this->start_date = $start_date = $_GET['start_date']?:$this->app->today;
		$this->end_date = $end_date = $_GET['end_date']?:$this->app->today;
        $this->status = $this->app->stickyGET('status');
        $this->show_overdue = $show_overdue = $this->app->stickyGET('show_overdue');

        $contact_id = $this->app->stickyGET('contact_id');

        $filter_form = $this->add('Form',null,'filter_form');
        $note_v = $filter_form->add('View');
        $filter_form->add('xepan\base\Controller_FLC')
		->showLables(true)
		->makePanelsCoppalsible(true)
		->layout([
				'period'=>'Filter (Date Range and See Overdue) Followups~c1~4~closed',
				'overdue'=>'c2~4',
				'FormButtons'=>'c3~4',
			]);

        $fld = $filter_form->addField('DateRangePicker','period')
                ->setStartDate($start_date)
                ->setEndDate($end_date)
                ->getFutureDatesSet()
                ->getBackDatesSet(false);
        $filter_form->addField('CheckBox','overdue','Show Overdue Followups');
		$filter_form->addSubmit("Filter")->addClass('btn btn-primary');
		
		if($filter_form->isSubmitted()){
			$this->js()->reload(['show_overdue'=>$filter_form['overdue'],'start_date'=>$fld->getStartDate()?:0,'end_date'=>$fld->getEndDate()?:0])->execute();
		}

		$my_followups = $this->add('xepan\hr\CRUD',['entity_name'=>'Followup','grid_class'=>'xepan\projects\View_TaskList'],'view');
		
		$status_array = [];	
		$status_array = [	'Pending'=>'Pending',
							'Inprogress'=>'Inprogress',
							'Assigned'=>'Assigned',
							'Submitted'=>'Submitted',
							'Completed'=>'Completed'
						];	
		
		$frm = $my_followups->grid->addQuickSearch(['task_name']);
		

		// $frm->add('xepan\base\Controller_FLC')
		// ->addContentSpot()
		// ->showLables(true)
		// ->makePanelsCoppalsible(true)
		// ->layout([
		// 		'q'=>'Search~c1~4~closed',
		// 		'task_status'=>'c2~4',
		// 		'FormButtons'=>'c3~4',
		// 	]);

		// $temp_status = ['Pending','Inprogress','Assigned'];

		// $count = 0;
		// if(is_array($frm->recall('task_status',false))){
		// 	foreach ($frm->recall('task_status',false) as $value) {
		// 		foreach ($temp_status as $v) {
		// 			if($v == $value)
		// 				$count++;
		// 		}
		// 	}
		// }
								
		// if((!$frm->recall('task_status',false)) || ($show_overdue AND $count==3)) $frm->memorize('task_status',['Pending','Inprogress','Assigned']);
		// $status = $frm->addField('Dropdown','task_status');
		// $status->setvalueList(['Pending'=>'Pending','Inprogress'=>'Inprogress','Assigned'=>'Assigned','Submitted'=>'Submitted','Completed'=>'Completed'])->setEmptyText('Select a status');
		// $status->setAttr(['multiple'=>'multiple']);
		// $status->setValueList($status_array);

		// $frm->addHook('applyFilter',function($f,$m){
		// 	if(!is_array($f['task_status'])) $f['task_status'] = explode(',',$f['task_status']);
			
		// 	if($f['task_status'] AND $m instanceOf \xepan\projects\Model_Task){
		// 		$m->addCondition('status',$f['task_status']);
		// 		$f->memorize('task_status',$f['task_status']);
		// 	}else{
		// 		$f->forget('task_status');
		// 	}
		// });

		if(!$my_followups->isEditing())
			$my_followups->grid->addPaginator(25);	
		
		// $status->js('change',$frm->js()->submit());

		$my_followups_model = $this->add('xepan\projects\Model_Task');
		
		// loading followups depending upon employees post permission level
		$post_m = $this->add('xepan\hr\Model_Post');
		$post_m->load($this->app->employee['post_id']);

		switch ($post_m['permission_level']) {
			
			case 'Sibling':
				$note_v->add('View')->set($this->app->employee['post'].' Post is defined to see Sibling followups and you are seeing everyones followups who are on same post as you are');
				$post_employees = $this->add('xepan\hr\Model_Employee');
				$post_employees->addCondition('post_id',$this->app->employee['post_id']);

				$employee = [];
				foreach ($post_employees as $emp){
					$employee [] = $emp->id;
				}

				$my_followups_model->addCondition(
					$my_followups_model->dsql()->orExpr()
						->where('assign_to_id',$employee)
						->where(
							$my_followups_model->dsql()->andExpr()
								->where('created_by_id',$employee)
								->where('assign_to_id',null)
							   )
				);

				break;
			case 'Department':
				$note_v->add('View')->set($this->app->employee['post'].' Post is defined to see Department followups and you are seeing everyones followups who are  in same department as you are');
				$department_employees = $this->add('xepan\hr\Model_Employee')
	    							         ->addCondition('department_id',$this->app->employee['department_id']);
				
				$my_followups_model->addCondition(
					$my_followups_model->dsql()->orExpr()
						->where('assign_to_id','in',$department_employees->fieldQuery('id'))
						->where(
							$my_followups_model->dsql()->andExpr()
								->where('created_by_id','in',$department_employees->fieldQuery('id'))
								->where('assign_to_id',null)
							   )
				);	
				break;
			case 'Global':				
				$note_v->add('View')->set($this->app->employee['post'].' Post is defined to see Global followups and you are seeing everyones followups');
				break;
			default: //SELF
				$my_followups_model->addCondition([['assign_to_id',$this->app->employee->id],['created_by_id',$this->app->employee->id]]);
				break;
		}
		
		if(!$my_followups->isEditing()){
			if($show_overdue){
				$my_followups_model->addCondition('starting_date','<=',$this->app->nextDate($this->end_date));
				// status
			}else{
				$my_followups_model->addCondition('starting_date','>',$this->start_date);
				$my_followups_model->addCondition('starting_date','<=',$this->app->nextDate($this->end_date));
			}
		}


		$my_followups_model->addCondition('type','Followup');

	    $my_followups_model->setOrder('updated_at','desc');
		

		if($my_followups->isEditing()){
			$my_followups->form->setLayout('view\task_form');
			$snooze_reminder_field = $my_followups->form->addField('checkbox','snooze_reminder','Enable Snoozing [Repetitive Reminder]');
		}

		$my_followups->setModel($my_followups_model,['contact_name','task_name','related_id','assign_to_image','reminder_time','priority','starting_date','deadline','estimate_time','set_reminder','remind_via','notify_to','snooze_duration','remind_unit','description','is_recurring','recurring_span','contact_organization'],['contact_organization','contact_name','assIgn_to_image','task_name','related_id','assign_to','reminder_time','priority','starting_date','deadline','estimate_time','set_reminder','remind_via','notify_to','snooze_duration','remind_unit','description','is_recurring','recurring_span','status']);
		$my_followups->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assign_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		
		$my_followups->form->getElement('related_id')->set($contact_id);
		$my_followups->form->getElement('starting_date')->js(true)->val('');
		$my_followups->form->getElement('deadline')->js(true)->val('');
		$my_followups->form->getElement('reminder_time')->js(true)->val('');
		$my_followups->form->getElement('related_id')->set($contact_id);
		
		$reminder_field = $my_followups->form->getElement('set_reminder'); 
		$recurring_field = $my_followups->form->getElement('is_recurring');

		if($my_followups->isEditing()){
			$my_followups->form->getElement('notify_to')
							->setAttr(['multiple'=>'multiple']);

			$my_followups->form->getElement('remind_via')
							->setAttr(['multiple'=>'multiple']);
											
			$reminder_field->js(true)->univ()->bindConditionalShow([
				true=>['remind_via','notify_to','reminder_time','snooze_reminder']
			],'div.atk-form-row');

			$snooze_reminder_field->js(true)->univ()->bindConditionalShow([
				true=>['snooze_reminder','snooze_duration','remind_unit']
			],'div.atk-form-row');
			
			$recurring_field->js(true)->univ()->bindConditionalShow([
				true=>['recurring_span']
			],'div.atk-form-row');

			if($my_followups->form->isSubmitted()){
				$my_followups->model['assign_to_id'] = $this->app->employee->id;
				// form field error if already added and is not same as before
				if(!$my_followups->form['snooze_reminder']){
					$my_followups->model['snooze_duration'] = null;
					$my_followups->form->model->save();
				}

			}
		
		}
	}

	function defaultTemplate(){
		return ['page\followups'];
	}
}