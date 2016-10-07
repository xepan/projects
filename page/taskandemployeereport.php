<?php

namespace xepan\projects;

class page_taskandemployeereport extends page_reportsidebar{
	public $title = "Task And Employee Report";

	function init(){
		parent::init();

		/*******************************************************************
		 GETTING VALUES FROM URL	
		********************************************************************/
		$from_date = $this->app->stickyGET('from_date')?:$this->app->today;
		$to_date = $this->app->stickyGET('to_date')?:$this->app->nextDate($this->app->today);

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

		$employee->addExpression('total_pending_tasks')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task')
				        ->addCondition('employee_id',$m->getElement('id'))
				        ->addCondition('status','Pending')
				        ->count();
		});		

		// total number of tasks employee worked on 
		// $employee->addExpression('tasks_worked_on')->set(function($m,$q){
		// 	return $this->add('xepan\projects\Model_Timesheet')
		// 		        ->addCondition('employee_id',$m->getElement('id'))
		// 		        ->_dsql()->group('task_id')
		// 		        ->count();
		// });

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

		// total_minutes_taken converted in hours
		$employee->addExpression('total_hours_taken')->set(function($m,$q){
			return $q->expr('([0])/60',[$m->getElement('total_minutes_taken')]);
		});

		/*******************************************************************
	 	 FORM TO ENTER INFORMATION
		********************************************************************/
		$form = $this->add('Form');
		$form->addField('DatePicker','from_date')->set($this->app->today);
		$form->addField('DatePicker','to_date')->set($this->app->today);
		$form->addSubmit('Get Report')->addclass('btn btn-primary btn-sm btn-block');	
	
		// GRID WILL BE ADDED ON THIS VIEW
		$view = $this->add('View');

		/*******************************************************************
		 ADDING GRID ON VIEW AND SETTING MODEL
		********************************************************************/
		$grid = $view->add('Grid');			
		$grid->setModel($employee,['name','total_tasks','total_pending_tasks','total_hours_alloted','total_estimated_hours','total_hours_taken']);
		/*******************************************************************
		 HANDLING FORM SUBMISSION
		********************************************************************/
		if($form->isSubmitted()){
			$array = [
						'from_date'=>$form['from_date'],
						'to_date'=>$form['to_date'],
					 ];
			$view->js()->reload($array)->execute();
		}
	}
}