<?php

namespace xepan\projects;

class Model_Widget_TaskStatus extends \xepan\hr\Model_Employee{
	public $start_date; 		
	public $end_date;

	function init(){
		parent::init();

     	$this->addCondition('status','Active');

		$this->addExpression('pending_works')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('type','Task')
				 ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition('received_at','>=',$this->start_date)
				 ->addCondition([['submitted_at','<',$this->end_date],['submitted_at',null]]);
			return $task->count();
		});

		$this->addExpression('please_receive')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('type','Task')
      	         ->addCondition('created_by_id','<>',$q->getField('id'))
      	         ->addCondition('created_at','>=',$this->start_date)
				 ->addCondition([['received_at','<',$this->end_date],['received_at',null]]);
            return $task->count();
		});

		$this->addExpression('received_so_far')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('type','Task')
				 ->addCondition('created_by_id','<>',$q->getField('id'))
      	         ->addCondition('received_at','>=',$this->start_date)
				 ->addCondition([['submitted_at','<',$this->end_date],['submitted_at',null]]);	 
			return $task->count();
		});

		$this->addExpression('total_tasks_assigned')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('type','Task')
                 ->addCondition('created_by_id',$q->getField('id'))
                 ->addCondition('created_at','>=',$this->start_date)
                 ->addCondition([['received_at','<',$this->end_date],['received_at',null]]);	
			return $task->count();
		});

		$this->addExpression('take_report_on_pending')->set(function($m,$q){
			$task  = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('type','Task')
                 ->addCondition('created_by_id',$q->getField('id'))
				 ->addCondition('received_at','>=',$this->start_date)
				 ->addCondition([['submitted_at','<',$this->end_date],['submitted_at',null]]);	
			return $task->count();
		});

		$this->addExpression('check_submitted')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('type','Task')
				 ->addCondition('created_by_id',$q->getField('id'))
				 ->addCondition('submitted_at','>=',$this->start_date)
				 ->addCondition([['completed_at','<',$this->end_date],['completed_at',null]]);
			return $task->count();
		});
	}
}