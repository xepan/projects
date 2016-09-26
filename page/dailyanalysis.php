<?php

namespace xepan\projects;

class page_dailyanalysis extends \xepan\projects\page_sidemenu{
	public $title="Daily Routine";
	function init(){
		parent::init();

		$employee_id = $this->app->stickyGET('contact_id');
		$project_id = $this->app->stickyGET('project_id');
		$on_date = $this->app->stickyGET('on_date');
		
		
		$model_employee = $this->add('xepan\projects\Model_Employee');
		$model_project = $this->add('xepan\projects\Model_Project');
		$model_timesheet = $this->add('xepan\projects\Model_Timesheet');

		$form = $this->add('Form',null,'form');
		
		$emp_field = $form->addField('dropdown','employee')->setEmptyText('All');
		$emp_field->setModel($model_employee);
		$emp_field->set($this->api->stickyGET('contact_id'));

		$form->addField('dropdown','project')->setEmptyText('All')->setModel($model_project);
		$form->addField('DatePicker','on_date');
		$form->addSubmit('Check');

		if($employee_id){
			$model_timesheet->addCondition('employee_id',$employee_id);
		}

		if($project_id){
			$model_timesheet->addCondition('project_id',$project_id);
		}

		if($on_date){
			$model_timesheet->addCondition('start_time','>',$on_date);
		}

		$grid = $this->add('xepan\hr\Grid',['no_records_message'=>'No task found'],'task',['view\task_timeline']);
		$grid->setModel($model_timesheet,['task','duration']);
		
		
		if($form->isSubmitted()){
			return $grid->js()->reload(['project_id'=>$form['project'],'on_data'=>$form['on_date']?:0,'employee_id'=>$form['employee']])->execute();
		}
	}

	function defaultTemplate(){
		return['view\dailyanalysis'];
	}
}