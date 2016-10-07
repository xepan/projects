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
		
		/*******************************************************************
		 EMPLOYEE TIMESHEET AND EXPRESSIONS
		********************************************************************/
		$task = $this->add('xepan\projects\Model_Task');
		
		$task->addExpression('time_consumed')->set(function($m,$q){
			$time_sheet = $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'total_duration']);
			$time_sheet->addCondition('task_id',$q->getField('id'));
			return $time_sheet->dsql()->del('fields')->field($q->expr('sec_to_time(SUM([0]))',[$time_sheet->getElement('duration')]));
		});

		if($project_id)
			$task->addCondition('project_id',$project_id);
		if($from_date)
			$task->addCondition('created_at','>=',$from_date);
		if($from_date)
			$task->addCondition('created_at','<=',$to_date);
		
		/*******************************************************************
	 	 FORM TO ENTER INFORMATION
		********************************************************************/	
		$form = $this->add('Form');
		$form->addField('DatePicker','from_date')->set($this->app->today);
		$form->addField('DatePicker','to_date')->set($this->app->today);
		$form->addField('Dropdown','project')->setModel('xepan\projects\Project');
		$form->addSubmit('Get Report')->addclass('btn btn-primary btn-sm btn-block');	

		// GRID WILL BE ADDED ON THIS VIEW
		$view = $this->add('View');

		/*******************************************************************
		 ADDING GRID ON VIEW AND SETTING MODEL
		********************************************************************/
		$grid = $view->add('Grid');			
		$grid->setModel($task,['task_name','starting_date','deadline','estimate_time','time_consumed']);
		// $grid->addFormatter('task_name','wrap');	
		/*******************************************************************
		 HANDLING FORM SUBMISSION
		********************************************************************/
		if($form->isSubmitted()){
			$array = [
						'from_date'=>$form['from_date'],
						'to_date'=>$form['to_date'],
						'project_id'=>$form['project'],
					 ];
			$view->js()->reload($array)->execute();
		}
		
	}
}