<?php

namespace xepan\projects;

class Model_EmployeeCommunicationActivity extends \xepan\hr\Model_Employee{
	public $from_date;
	public $to_date;
	function init(){
		parent::init();
		// echo "string".$this->from_date;
		$this->addCondition('status','Active');
		$this->addExpression('assign_to_pending_task')->set(function($m,$q){
			// return $q->getField('id');
			$task = $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_to_assigntask']);
				return 	$task->addCondition('assign_to_id',$q->getField('id'))
						->addCondition('assign_to_id','<>',null)
						->addCondition('status','Pending')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('assign_to_inprogress_task')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_to_assigntask'])
						->addCondition('assign_to_id',$q->getField('id'))
						->addCondition('assign_to_id','<>',null)
						->addCondition('status','Inprogress')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->count();
		});
		$this->addExpression('assign_to_complete_task')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_to_assigntask'])
						->addCondition('assign_to_id',$q->getField('id'))
						->addCondition('assign_to_id','<>',null)
						->addCondition('status','Completed')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('assign_by_pending_task')->set(function($m,$q){
			// return $q->getField('id');
			return $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_to_assigntask'])
						->addCondition('created_by_id',$q->getField('id'))
						->addCondition('created_by_id','<>',null)
						->addCondition('status','Pending')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('assign_by_inprogress_task')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_to_assigntask'])
						->addCondition('created_by_id',$q->getField('id'))
						->addCondition('created_by_id','<>',null)
						->addCondition('status','Inprogress')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->count();
		});
		$this->addExpression('assign_by_complete_task')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_to_assigntask'])
						->addCondition('created_by_id',$q->getField('id'))
						->addCondition('created_by_id','<>',null)
						->addCondition('status','Completed')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('overdue_task')->set(function($m,$q){
			$task =  $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_to_assigntask']);
			$task->addCondition('status',['Pending','Inprogress','Assigned'])
		    	 	->addCondition($task->dsql()->orExpr()
		    		->where('assign_to_id',$q->getField('id'))
		    		->where($task->dsql()->andExpr()
					->where('created_by_id',$q->getField('id'))
					->where('assign_to_id',null)));
			$task->addCondition('deadline','<',$this->app->now);			
			$task->addCondition('status','<>','Completed');
			$task->addCondition('created_at','>=',$this->from_date);
			$task->addCondition('created_at','<',$this->api->nextDate($this->to_date));
			return $task->count();		
		});

		// $this->addExpression('total_received_message')->set(function($m,$q){
		// 	return $this->add('xepan\communication\Model_Communication_MessageReceived')
		// 				->addCondition('to_id',$q->getField('id'))
		// 				->count();

		// });
		$this->addExpression('total_send_message')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication_MessageSent')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->addCondition('created_by_id',$q->getField('id'))
						->count();
		});
		// $this->addExpression('total_received_emails')->set(function($m,$q){
		// 	return $this->add('xepan\communication\Model_Communication_Email_Received')
		// 				->addCondition('to_id',$q->getField('id'))
		// 				->count();

		// });
		$this->addExpression('total_send_emails')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication_Email_Sent')
						->addCondition('created_by_id',$q->getField('id'))
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->count();
		});
		// $this->addExpression('total_received_emails');
		// $this->addExpression('total_send_emails');
		
		// $this->addExpression('running_task')->set(function($m,$q){
		// 	return $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'running_task'])
		// 				->addCondition('endtime',null)
		// 				->addCondition('employee_id',$q->getField('id'))
		// 				->setOrder('starttime','desc')
		// 				->setLimit(1)
		// 				->fieldQuery('task');
		// })->sortable(true);


		// $this->addExpression('running_task_id')->set(function($m,$q){
		// 	return $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'running_task'])
		// 				->addCondition('endtime',null)
		// 				->addCondition('employee_id',$q->getField('id'))
		// 				->setOrder('starttime','desc')
		// 				->setLimit(1)
		// 				->fieldQuery('task_id');
		// })->sortable(true);

		// $this->addExpression('running_task_since')->set(function($m,$q){
		// 	return $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'running_task'])
		// 				->addCondition('endtime',null)
		// 				->addCondition('employee_id',$q->getField('id'))
		// 				->setOrder('starttime','desc')
		// 				->setLimit(1)
		// 				->fieldQuery('duration');
		// })->sortable(true);

		// $this->addExpression('project')->set(function($m,$q){
		// 	$p=$this->add('xepan\projects\Model_Project');
		// 	$task_j = $p->join('task.project_id');
		// 	$task_j->addField('task_id','id');
		// 	$p->addCondition($q->expr('[0]=[1]',[$p->getElement('task_id'),$m->getField('running_task_id')]));
		// 	return $p->fieldQuery('name');
		// })->sortable(true);

		// $this->hasMany('xepan\projects\Task','assign_to_id');

		// $this->addExpression('pending_tasks_count')->set(function ($m,$q){
		// 	return $m->refSQL('xepan\projects\Task')
		// 				->addCondition('status','Pending')
		// 				->count();
		// })->sortable(true);



		// $this->addExpression('performance')->set("'Todo'");
	}
}