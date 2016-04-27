<?php

namespace xepan\projects;

class page_dailyanalysis extends \xepan\projects\page_sidemenu{
	function init(){
		parent::init();

		$employee_id = $this->app->stickyGET('contact_id');
		$project = $this->app->stickyGET('project_id');
		
		$date = $this->app->stickyGET('date');
		
		$model_employee = $this->add('xepan\projects\Model_Employee')->load($employee_id);
		$model_project = $this->add('xepan\projects\Model_Project');
		$model_timesheet = $this->add('xepan\projects\Model_Timesheet')->addCondition('employee_id',$employee_id);

		$form = $this->add('Form',null,'form');
		$form->addField('DatePicker','date');
		$form->addSubmit('Check');
		
		
		if($date){
			if($project)
				$model_timesheet->addCondition('');

			foreach ($model_timesheet as $persued_tasks) {
				$view_task = $this->add('View',null,'task');
				$startdate = date('Y-m-d',strtotime($model_timesheet['starttime']));
				if($startdate == $date)
				{
					$view_task->set($persued_tasks['task']); 
				}
			}
		}
		
		$view_task_url = $view_task->app->url(null,['cut_object'=>$view_task->name]);
		if($form->isSubmitted()){

			return $view_task->js()->univ()->reload(['project_id'=>$form['name'],'date'=>$form['date']],null,$view_task_url)->execute();
		}
	}

	function defaultTemplate(){
		return['view\dailyanalysis'];
	}
}