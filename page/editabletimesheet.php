<?php

namespace xepan\projects;

class page_editabletimesheet extends \xepan\base\Page{
	public $title = "My Timesheet";

	function init(){
		parent::init();

		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition($task->dsql()->orExpr()
	    		->where('assign_to_id',$this->app->employee->id)
				->where('created_by_id',$this->app->employee->id)
			);
		$task->addCondition('status','<>','Completed');

		$form = $this->add('Form');
		$task_field = $form->addField('xepan\base\DropDown','task');
		$task_field->setEmptyText('Please select a task or add new by typing');
		$task_field->setModel($task);

		$task_field->validate_values= false;
		$task_field->select_menu_options=['tags'=>true];
		$starttime_field = $form->addField('TimePicker','starttime');
		$endtime_field = $form->addField('TimePicker','endtime');
		
		$starttime_field
				->setOption('showMeridian',false)
				->setOption('minuteStep',1)
				->setOption('showSeconds',true);
		$endtime_field
				->setOption('showMeridian',false)
				->setOption('minuteStep',1)
				->setOption('showSeconds',true);

		$form->addSubmit('Save')->addClass('btn btn-primary');
		
		$timesheet_m = $this->add('xepan\projects\Model_Timesheet');
		$timesheet_m->addCondition('employee_id',$this->app->employee->id);
		$timesheet_m->addCondition('starttime','>=',$this->app->today);
		$timesheet_m->acl = 'xepan\projects\Model_Task';
		$grid = $this->add('xepan\hr\CRUD');
		$grid->setModel($timesheet_m,['task','starttime','endtime','duration']);
		$grid->grid->removeColumn('action');
		$grid->grid->removeColumn('attachment_icon');

		if($form->isSubmitted()){
			$timestamp = $this->app->today;
			$timestamp .= ' '.$form['starttime'];
			$starting_time = date('Y-m-d H:i:s',strtotime($timestamp));

			$timestamp = $this->app->today;
			$timestamp .= ' '.$form['endtime'];
			$ending_time = date('Y-m-d H:i:s',strtotime($timestamp));
			
			$model_task = $this->add('xepan\projects\Model_Task');
			$model_task->addCondition($model_task->dsql()->orExpr()
	    					->where('assign_to_id',$this->app->employee->id)
	    					->where('created_by_id',$this->app->employee->id)
	    					);	
			$model_task->tryLoadBy('id',$form['task']);
			
			if(!$model_task->loaded()){
				if(!$form['task'])
					$form->displayError('task','Add a new task or select from old');

				$model_task['task_name']  = $form['task'];
				$model_task['assign_to_id'] = $this->app->employee->id;
				$model_task['created_by_id'] = $this->app->employee->id;
				$model_task['status'] = 'Pending';
				$model_task['created_at'] = $this->app->now;
				$model_task->save();
			}			
			
			$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
			$model_timesheet['employee_id'] = $this->app->employee->id;			
			$model_timesheet['task_id'] = $model_task->id;			
			$model_timesheet['starttime'] = $starting_time; 			
			$model_timesheet['endtime'] = $ending_time;		
			$model_timesheet->save();

			$form->js('true',$grid->js()->reload())->univ()->successMessage('Saved')->execute();
		}
	}
}