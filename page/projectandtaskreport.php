<?php

namespace xepan\projects;

class page_projectandtaskreport extends page_reportsidebar{
	public $title = "Project And Task Report";
	function init(){
		parent::init();

		/*******************************************************************
		 GETTING VALUES FROM URL	
		********************************************************************/
		$from_date = $this->app->stickyGET('from_date')?:$this->app->today;
		$to_date = $this->app->stickyGET('to_date')?:$this->app->nextDate($this->app->today);
		$project_id = $this->app->stickyGET('project_id');
		$task_id = $this->app->stickyGET('task_id');
		
		/*******************************************************************
		 EMPLOYEE TIMESHEET AND EXPRESSIONS
		********************************************************************/
		$timesheet = $this->add('xepan\projects\Model_Timesheet');
		$timesheet->addCondition('task_id',$task_id);

		/*******************************************************************
	 	 FORM TO ENTER INFORMATION
		********************************************************************/	
		$task = $this->add('xepan\projects\Model_Task');
		$form = $this->add('Form');
		$form->addField('DatePicker','from_date')->set($this->app->today);
		$form->addField('DatePicker','to_date')->set($this->app->today);
		$form->addField('Dropdown','projects')->setModel('xepan\projects\Project');
		$task_field = $form->addField('autocomplete/Basic','task');
		$task_field->setModel($task);
		$form->addSubmit('Get Report')->addclass('btn btn-primary btn-sm btn-block');	

		// GRID WILL BE ADDED ON THIS VIEW
		$view = $this->add('View');

		/*******************************************************************
		 ADDING GRID ON VIEW AND SETTING MODEL
		********************************************************************/
		$grid = $view->add('Grid');			
		$grid->setModel($timesheet,['employee','starttime','endtime','duration']);
		
		/*******************************************************************
		 HANDLING FORM SUBMISSION
		********************************************************************/
		if($form->isSubmitted()){
			$array = [
						'from_date'=>$form['from_date'],
						'to_date'=>$form['to_date'],
						'task_id'=>$form['task'],
					 ];
			$view->js()->reload($array)->execute();
		}
		
	}
}