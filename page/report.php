<?php

namespace xepan\projects;

class page_report extends \xepan\base\Page{
	public $title = "Project Report";

	function init(){
		parent::init();
		
		/*******************************************************************
		 GETTING VALUES FROM URL	
		********************************************************************/
		$from_date = $this->app->stickyGET('from_date')?:$this->app->today;
		$to_date = $this->app->stickyGET('to_date')?:$this->app->nextDate($this->app->today);
		$report_type = $this->app->stickyGET('report_type');

		/*******************************************************************
		 EMPLOYEE MODEL AND EXPRESSIONS
		********************************************************************/
		$employee = $this->add('xepan\hr\Model_Employee');

		// total number of tasks alloted to employee
		$employee->addExpression('total_tasks')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task')
				        ->addCondition('employee_id',$m->getElement('id'))
				        ->count();
		});

		// total number of tasks employee worked on 
		$employee->addExpression('tasks_worked_on')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Timesheet')
				        ->addCondition('employee_id',$m->getElement('id'))
				        ->_dsql()->group('task_id')
				        ->count();
		});

		// total hours alloted 
		$employee->addExpression('total_hours_alloted')->set(function($m,$q){
			$task_m = $this->add('xepan\projects\Model_Task',['table_alias'=>'emptsk'])
				           ->addCondition('employee_id',$q->getField('id'));
			$task_m->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['HOUR',$q->getField('starting_date'),$q->getField('deadline')]);
			});

			return $task_m->_dsql()->del('fields')->field($q->expr('sum([0])',[$task_m->getElement('diff_time')]));
		});

		// total amount of estimate hours
		$employee->addExpression('total_estimated_hours')->set(function($m,$q){
			$task_m = $this->add('xepan\projects\Model_Task')
				           ->addCondition('employee_id',$m->getElement('id'));
			
			return $task_m->_dsql()->del('fields')->field($q->expr('sum([0])',[$task_m->getElement('estimate_time')]));
		});

		// total amount of time employee worked 
		$employee->addExpression('total_minutes_taken')->set(function($m,$q){
			$timesheet_m = $this->add('xepan\projects\Model_Timesheet')
				                ->addCondition('employee_id',$m->getElement('id'));
			$timesheet_m->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['MINUTE',$q->getField('starttime'),$q->getField('endtime')]);
			});
			
			return $timesheet_m->_dsql()->del('fields')->field($q->expr('sum([0])',[$timesheet_m->getElement('diff_time')]));
		});

		$employee->addExpression('total_hours_taken')->set(function($m,$q){
			return $q->expr('([0])/60',[$m->getElement('total_minutes_taken')]);
		});	
		
		/*******************************************************************
		 PROJECT MODEL AND EXPRESSIONS	
		********************************************************************/
		$project = $this->add('xepan\projects\Model_Project');
		
		// total hours consumed in project
		$project->addExpression('total_hours_consumed')->set(function($m,$q){
			return "'0'";
		});

		// total number of employees worked on project
		$project->addExpression('total_employees_worked')->set(function($m,$q){
			return "'0'";
		});

		// delay
		/*******************************************************************
		 TASK MODEL AND EXPRESSIONS	
		********************************************************************/
		$task = $this->add('xepan\projects\Model_Task');

		/*******************************************************************
	 	 FORM TO ENTER INFORMATION
		********************************************************************/
		$form = $this->add('Form');
		$form->addField('DatePicker','from_date')->set($this->app->today);
		$form->addField('DatePicker','to_date')->set($this->app->today);
		$form->addField('DropDown','report_type')->setValueList(['employee'=>'Employee','project'=>'Project','task'=>'Task'])->setEmptyText('Please select a report type');
		$form->addSubmit('Get Report')->addclass('btn btn-primary btn-sm btn-block');
		
		// GRID WILL BE ADDED ON THIS VIEW
		$view = $this->add('View');

		/*******************************************************************
		 IF REPORT_TYPE IS PRESENT IN STICKYGET VARIABLE THEN ADD GRID
		********************************************************************/
		if($report_type){
			$grid = $view->add('Grid');
			
			if($report_type === 'employee')	
				$grid->setModel($employee,['name','total_tasks','tasks_worked_on','total_hours_alloted','total_estimated_hours','total_hours_taken']);
			if($report_type === 'project')	
				$grid->setModel($project);
			if($report_type === 'task')	
				$grid->setModel($task);
		}

		/*******************************************************************
		 HANDLING FORM SUBMISSION
		********************************************************************/
		if($form->isSubmitted()){
			$array = [
						'report_type'=>$form['report_type'],
						'from_date'=>$form['from_date'],
						'to_date'=>$form['to_date'],
					 ];
			$view->js()->reload($array)->execute();
		}
	}
}