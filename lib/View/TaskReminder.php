<?php

namespace xepan\projects;

class View_TaskReminder extends \View{
	function init(){
		parent::init();
		
		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('set_reminder',true);
		$task->addCondition('created_by_id',$this->app->employee->id);
		$task->addCondition('employee_id',$this->app->employee->id);
		
		$reminder_crud = $this->add('xepan\hr\CRUD',['entity_name'=>'Reminder'],null,['view\taskreminder']);
		
		if($reminder_crud->isEditing()){
			$reminder_crud->form->setLayout('view\reminder_form');
			$reminder_crud->form->addField('checkbox','make_task','');
		}
		
		$reminder_crud->setModel($task,['task_name','notify_to','starting_date','remind_via','remind_value','remind_unit','is_recurring','recurring_span','description'])->setOrder('created_at','desc');

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
				$task['is_reminder_only'] = true;		
				
				if($reminder_crud->form['make_task'])
					$task['is_reminder_only'] = false;		
				$task->save();	 					 					 					 									
				// if($task['notify_to']) $task['notify_to'] = '';
				// if($task['remind_via']) $task['remind_via'] = '';
			}
		}

		$reminder_crud->grid->addHook('formatRow',function($g){						
			$g->current_row['reminder_time'] = date("Y-m-d H:i:s", strtotime('-'.$g->model['remind_value'].' '.$g->model['remind_unit'], strtotime($g->model['starting_date'])));		
		});		
	}
}