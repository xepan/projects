<?php

namespace xepan\projects;

class Model_Widget_AccountableSystemUse extends \xepan\hr\Model_Employee{
	public $start_date; 		
	public $end_date;
	public $entity;
	public $dept_id;
	public $employee_id;

	function init(){
		parent::init();

     	$this->addCondition('status','Active');
     	
     	if($this->entity=='Personal')
     		$this->addCondition('id',$this->app->employee->id);

     	if($this->entity=='Employee' AND $this->employee_id != null)
     		$this->addCondition('id',$this->employee_id);

     	if($this->entity == 'Department'){
     		if($this->dept_id)
     			$this->addCondition('department_id',$this->dept_id);
     		else
     			$this->addCondition('department_id',$this->app->employee['department_id']);
     	}


		$this->addExpression('pending_works')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('type','Task')
				 ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition('status','Pending')
				 ->addCondition('created_at','>=',$this->start_date)
				 ->addCondition('created_at','<=',$this->end_date);
			return $task->count();
		});

		$this->addExpression('please_receive')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('type','Task')
      	         ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition('status','Assigned')
				 ->addCondition('created_at','>=',$this->start_date)
				 ->addCondition('created_at','<=',$this->end_date);
            return $task->count();
		});

		$this->addExpression('received_so_far')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('type','Task')
				 ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition('created_at','>=',$this->start_date)
				 ->addCondition('created_at','<=',$this->end_date);	 
			return $task->count();
		});

		$this->addExpression('total_tasks_assigned')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('type','Task')
                 ->addCondition('created_by_id',$q->getField('id'))
                 ->addCondition('created_at','>=',$this->start_date)
				 ->addCondition('created_at','<=',$this->end_date);
			return $task->count();
		});

		$this->addExpression('take_report_on_pending')->set(function($m,$q){
			$task  = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('type','Task')
                 ->addCondition('created_by_id',$q->getField('id'))
				 ->addCondition('status',['Pending','Assigned'])
				 ->addCondition('created_at','>=',$this->start_date)
				 ->addCondition('created_at','<=',$this->end_date);
			return $task->count();
		});

		$this->addExpression('check_submitted')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('type','Task')
				 ->addCondition('created_by_id',$q->getField('id'))
				 ->addCondition('status','Submitted')
				 ->addCondition('created_at','>=',$this->start_date)
				 ->addCondition('created_at','<=',$this->end_date);
			return $task->count();
		});
	}
}