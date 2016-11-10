<?php
namespace xepan\projects;

class page_test extends \Page{
	function init(){
		parent::init();

		$this->start_date = $start_date = $_GET['start_date']?:date("Y-m-d", strtotime('-29 days', strtotime($this->app->today))); 		
		$this->end_date = $end_date = $_GET['end_date']?:$this->app->today;

		/*************************************************************
				ACCOUNTABLE SYSTEM USE
		**************************************************************/

     	$model = $this->add('xepan\hr\Model_Employee');
     	$model->addCondition('status','Active');

		$model->addExpression('pending_works')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('type','Task')
				 ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition('status','Pending')
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
			return $task->count();
		});

		$model->addExpression('please_receive')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('type','Task')
      	         ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition('status','Assigned')
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
            return $task->count();
		});

		$model->addExpression('received_so_far')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('type','Task')
				 ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));	 
			return $task->count();
		});

		$model->addExpression('total_tasks_assigned')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('type','Task')
                 ->addCondition('created_by_id',$q->getField('id'))
                 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
			return $task->count();
		});

		$model->addExpression('take_report_on_pending')->set(function($m,$q){
			$task  = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('type','Task')
                 ->addCondition('created_by_id',$q->getField('id'))
				 ->addCondition('status',['Pending','Assigned'])
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
			return $task->count();
		});

		$model->addExpression('check_submitted')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('type','Task')
				 ->addCondition('created_by_id',$q->getField('id'))
				 ->addCondition('status','Submitted')
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
			return $task->count();
		});

		$accountable_w = $this->add('xepan\projects\Widget_AccountableSystemUse');
		$accountable_w->setModel($model);

		/*************************************************************
				PROJECT HOUR CONSUMPTION
		**************************************************************/

		$project = $this->add('xepan\projects\Model_Project');

		$project->addExpression('Estimate')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'))
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));	
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('estimate_time')]));
		});

		$project->addExpression('Alloted')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'))
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));	

			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['HOUR',$q->getField('starting_date'),$q->getField('deadline')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('diff_time')]));
		}); 

		$project->addExpression('Consumed')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'));
			$task->addCondition('status','Completed')
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));	

			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['HOUR',$q->getField('starting_date'),$q->getField('updated_at')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('diff_time')]));
		}); 
		
		$project->addCondition([['Estimate','>',0],['Alloted','>',0],['Consumed','>',0]]);
		$hour_consumptoin = $this->add('xepan\projects\Widget_ProjectHourConsumption');
		$hour_consumptoin->setModel($project);
		
		/*************************************************************
				EMPLOYEE TIMESHEET
		**************************************************************/
		$task = $this->add('xepan\projects\Model_Task')
				 	 ->addCondition('type','Task');

		$task->addExpression('time_consumed')->set(function($m,$q){
			$time_sheet = $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'total_duration']);
			$time_sheet->addCondition('task_id',$q->getField('id'));
			return $time_sheet->dsql()->del('fields')->field($q->expr('sec_to_time(SUM([0]))',[$time_sheet->getElement('duration')]));
		});

		$employee_timesheet = $this->add('xepan\projects\Widget_EmployeeTimesheet');
		$employee_timesheet->setModel($task);
		
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