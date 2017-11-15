<?php

namespace xepan\projects;

class View_TaskReminder extends \View{
	function init(){
		parent::init();
		
		$tabs = $this->add('Tabs');
        $to_be_reminded = $tabs->addTab('Reminders');
        $reminded = $tabs->addTab('Reminded');
        
		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('set_reminder',true);
		$task->addCondition('type','Reminder');
		$task->addCondition('created_by_id',$this->app->employee->id);
		$task->setOrder('created_at','desc');
		$task->addCondition([['is_reminded',null],['is_reminded',0]]);
		$task->addCondition('assign_to_id',$this->app->employee->id);
		$task->addCondition('remind_value',0);
		$task->addCondition('remind_unit','Minutes');

		$reminded_task = $this->add('xepan\projects\Model_Task');
		$reminded_task->addCondition('set_reminder',true);
		$reminded_task->addCondition('type','Reminder');
		$reminded_task->addCondition('created_by_id',$this->app->employee->id);
		$reminded_task->addCondition('is_reminded',true);
		$reminded_task->setOrder('created_at','desc');

		$reminded_crud = $reminded->add('xepan\hr\CRUD',['allow_add'=>false],null,['view\taskreminder']);
		$reminded_crud->setModel($reminded_task);

		$reminder_crud = $to_be_reminded->add('xepan\hr\CRUD',['entity_name'=>'Reminder'],null,['view\taskreminder']);
		
		if($reminder_crud->isEditing()){
			$reminder_crud->form->setLayout('view\reminder_form');
		}
		

		$task->addHook('beforeSave',[$this,'formValidations']);
		$reminder_crud->setModel($task,['starting_date','task_name','notify_to','reminder_time','remind_via','is_recurring','recurring_span'])->setOrder('created_at','desc');

		if($reminder_crud->isEditing() AND !$reminder_crud->model->id){
			$reminder_time_field = $reminder_crud->form->getElement('reminder_time')->js(true)->val('');
		}

		if($reminder_crud->isEditing()){
			
			$followup_field = $reminder_crud->form->getElement('is_recurring');

			$followup_field->js(true)->univ()->bindConditionalShow([
			true=>['recurring_span','snooze_duration'],
			],'div.atk-form-row');
			
			if($reminder_crud->model->id){
				$task = $this->add('xepan\projects\Model_Task')->load($reminder_crud->model->id);
				$temp = [];
				$temp = explode(',', $task['notify_to']);

				$temp1 = [];
				$temp1 = explode(',', $task['remind_via']);																																														

				$reminder_crud->form->getElement('notify_to')->set($temp)->js(true)->trigger('changed');
				$reminder_crud->form->getElement('remind_via')->set($temp1)->js(true)->trigger('changed');
			}


			$reminder_crud->form->getElement('notify_to')
							->setAttr(['multiple'=>'multiple']);

			$reminder_crud->form->getElement('remind_via')
							->setAttr(['multiple'=>'multiple']);
		
			if($reminder_crud->form->isSubmitted()){				
				$m = $reminder_crud->model;
				// starting date and deadline are filled to avoide deadline is smaller then starting date condition
				// starting date and deadline are filled to avoide null condition in recurring function
				// starting date and deadline spot are display none in template
				$m['starting_date'] = $this->app->now;		
				$m['deadline'] = $m['starting_date'];		
				$m['is_reminder_only'] = true;		
				$m['type'] = 'Reminder';		
				$m->save();	 					 					 					 									
			}
		}

		$reminder_crud->grid->addHook('formatRow',function($g){								
			if($g->model['is_recurring']){
				$g->current_row_html['recurring'] = 'alert alert-info';		
				$g->current_row_html['recurring_task_info'] = '[recurring Reminder]';
			}else{
				$g->current_row_html['recurring'] = ' ';		
				$g->current_row_html['recurring_task_info'] = ' ';
			}

			if($g->model['is_reminded']){
				$g->current_row_html['edit'] = ' ';
			}else{
				$g->current_row_html['dummy_spot'] = ' ';
			}

		});		

		$reminded_crud->grid->addHook('formatRow',function($g){						
			if($g->model['is_reminded']){
				$g->current_row_html['edit'] = ' ';
			}else{
				$g->current_row_html['dummy_spot'] = ' ';
			}
		});

		$reminder_crud->js('click')->_selector('.xepan-reminder-view-task')->univ()->frameURL('YOUR TASKS',[$this->api->url('xepan_projects_mytasks')]);
	}

	function formValidations($m){
		if($m['remind_via'] == null || $m['notify_to'] == null)
			$this->app->js()->univ()->alert('Remind Via And Remind To Are Compulsory')->execute();			
		
		if($m['is_recurring'] == true AND $m['recurring_span'] == '')
			throw $this->exception('Time gap is required','ValidityCheck')
							->setField('recurring_span');
	}
}