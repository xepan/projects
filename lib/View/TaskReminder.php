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
		$task->addCondition('created_by_id',$this->app->employee->id);
		$task->addCondition('is_reminded',false);
		$task->setOrder('created_at','desc');

		$reminded_task = $this->add('xepan\projects\Model_Task');
		$reminded_task->addCondition('set_reminder',true);
		$reminded_task->addCondition('created_by_id',$this->app->employee->id);
		$reminded_task->addCondition('is_reminded',true);
		$reminded_task->setOrder('created_at','desc');

		$reminded_crud = $reminded->add('xepan\hr\CRUD',['allow_add'=>false],null,['view\taskreminder']);
		$reminded_crud->setModel($reminded_task);

		$reminder_crud = $to_be_reminded->add('xepan\hr\CRUD',['entity_name'=>'Reminder'],null,['view\taskreminder']);
		
		if($reminder_crud->isEditing()){
			$reminder_crud->form->setLayout('view\reminder_form');
			$reminder_crud->form->addField('checkbox','make_task','');
		}
		
		$task->addHook('beforeSave',[$this,'formValidations']);
		$reminder_crud->setModel($task,['is_reminder_only','assign_to_id','task_name','notify_to','starting_date','remind_via','remind_value','remind_unit','is_recurring','recurring_span','description'])->setOrder('created_at','desc');

		if($reminder_crud->isEditing()){
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
				$m['is_reminder_only'] = true;		
				$m['type'] = 'Reminder';		
				if($reminder_crud->form['make_task']){
					$m['is_reminder_only'] = false;		
					$m['type'] = 'Task';		
				}
				$m->save();	 					 					 					 									
			}
		}

		$reminder_crud->grid->addHook('formatRow',function($g){						
			$g->current_row['reminder_time'] = date("Y-m-d H:i:s", strtotime('-'.$g->model['remind_value'].' '.$g->model['remind_unit'], strtotime($g->model['starting_date'])));		
			
			if($g->model['is_recurring']){
				$g->current_row_html['recurring_task_info'] = 'Recurring';		
			}else{
				$g->current_row_html['recurring_task_info'] = ' ';
			}

			if($g->model['is_reminder_only']){
				$g->current_row_html['is_task'] = ' ';		
				$g->current_row_html['class'] = ' ';		
			}else{
				$g->current_row_html['is_task'] = 'View Task';		
				$g->current_row_html['class'] = 'fa fa-tasks';
			}
		});		

		$reminder_crud->js('click')->_selector('.xepan-reminder-view-task')->univ()->frameURL('YOUR TASKS',[$this->api->url('xepan_projects_mytasks')]);
		
	}

	function formValidations($m){
		if($m['remind_via'] == '' || $m['notify_to'] == '' || $m['remind_value'] == '' || $m['remind_unit'] == '')
			$this->app->js()->univ()->alert('Fill all the fields of `Set Task Reminder`')->execute();			
		
		if($m['is_recurring'] == true AND $m['recurring_span'] == '')
			throw $this->exception('Time gap is required','ValidityCheck')
							->setField('recurring_span');
	}
}