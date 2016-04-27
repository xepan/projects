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
		$form->addField('dropdown','name','Project Name')->setEmptyText('All')->setModel($model_project);
		$form->addField('DatePicker','date','Start Date');
		$form->addSubmit('Check');
		$view_task = $this->add('View',null,'task');
		
		if($date){
			if($project)
			{
				//condition for project
			}

			$model_timesheet->addCondition('starttime','>=',$date);
			// $model_timesheet->addCondition('endtime','<=',$date);

			$grid = $view_task->add('xepan\hr\Grid',null,null,['view\task_timeline'])->setModel($model_timesheet,['task','duration'],['task_id','duration']);
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