<?php

namespace xepan\projects;

class View_TaskReminder extends \View{
	function init(){
		parent::init();
		
		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('set_reminder',true);
		$task->addCondition('employee_id',$this->app->employee->id);
		$task->addCondition('created_by_id',$this->app->employee->id);
		
		$reminder_crud = $this->add('xepan\hr\CRUD',['no_records_message'=>'No Reminders'],null,['view\taskreminder']);
		$reminder_crud->setModel($task,['task_name','description','starting_date','deadline','set_reminder','remind_via','remind_value','remind_unit']);


		if($reminder_crud->isEditing()){
			$reminder_crud->form->setLayout('view\reminder_form');
			$reminder_crud->form->getElement('Add')->addClass('btn btn-primary');
			$reminder_crud->form->getElement('remind_via')
							->addClass('multiselect-full-width')
							->setAttr(['multiple'=>'multiple']);
		}

		$reminder_crud->grid->addHook('formatRow',function($g){						
			$g->current_row['reminder_time'] = date("Y-m-d H:i:s", strtotime('-'.$g->model['remind_value'].' '.$g->model['remind_unit'], strtotime($g->model['starting_date'])));		
		});

		$task->reminder();
	}
}