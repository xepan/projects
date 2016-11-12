<?php
namespace xepan\projects;

class page_test extends \Page{
	function init(){
		parent::init();

		/*************************************************************
				EMPLOYEE TASK STATUS
		**************************************************************/

		$employee_task_status = $this->add('xepan\hr\Model_Employee');
		// total number of tasks alloted to employee
		$employee_task_status->addExpression('total_tasks')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task')
				 	 	->addCondition('type','Task')
				        ->addCondition('assign_to_id',$m->getElement('id'))
				        ->count();
		});

		$employee_task_status->addExpression('total_pending_tasks')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task')
				 	 	->addCondition('type','Task')
				        ->addCondition('assign_to_id',$m->getElement('id'))
				        ->addCondition('status','Pending')
				        ->count();
		});		

		// total hours alloted 
		$employee_task_status->addExpression('total_hours_alloted')->set(function($m,$q){
			$task_m = $this->add('xepan\projects\Model_Task',['table_alias'=>'emptsk'])
				 	 		->addCondition('type','Task')
				            ->addCondition('assign_to_id',$q->getField('id'));
			$task_m->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['HOUR',$q->getField('starting_date'),$q->getField('deadline')]);
			});

			return $task_m->_dsql()->del('fields')->field($q->expr('sum([0])',[$task_m->getElement('diff_time')]));
		});

		// total amount of estimate hours
		$employee_task_status->addExpression('total_estimated_hours')->set(function($m,$q){
			$task_m = $this->add('xepan\projects\Model_Task')
				 	 	   ->addCondition('type','Task')
				           ->addCondition('assign_to_id',$m->getElement('id'));
			
			return $task_m->_dsql()->del('fields')->field($q->expr('sum([0])',[$task_m->getElement('estimate_time')]));
		});

		// total amount of time employee worked 
		$employee_task_status->addExpression('total_minutes_taken')->set(function($m,$q){
			$timesheet_m = $this->add('xepan\projects\Model_Timesheet')
				                ->addCondition('employee_id',$m->getElement('id'));
			$timesheet_m->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['MINUTE',$q->getField('starttime'),$q->getField('endtime')]);
			});
			
			return $timesheet_m->_dsql()->del('fields')->field($q->expr('sum([0])',[$timesheet_m->getElement('diff_time')]));
		});

		// total_minutes_taken converted in hours
		$employee_task_status->addExpression('total_hours_taken')->set(function($m,$q){
			return $q->expr('([0])/60',[$m->getElement('total_minutes_taken')]);
		});

		$task_status = $this->add('xepan\projects\Widget_EmployeeTaskStatus');
		$task_status->setModel($employee_task_status);
	}
}