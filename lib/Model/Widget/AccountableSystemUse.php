<?php

namespace xepan\projects;

class Model_Widget_AccountableSystemUse extends \xepan\hr\Model_Employee{
	public $start_date; 		
	public $end_date;
	public $entity;

	function init(){
		parent::init();

     	$this->addCondition('status','Active');
     	
     	if($this->entity)
     		$this->addCondition('id',$this->app->employee->id);

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