<?php

namespace xepan\projects;

class Model_Widgets_AccountableSystemUse extends \xepan\hr\Model_Employee{
	$start_date; 		
	$end_date;

	function init(){
		parent::init();

     	$this->addCondition('status','Active');

		$this->addExpression('pending_works')->set(function($m,$q){
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

		$this->addExpression('please_receive')->set(function($m,$q){
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

		$this->addExpression('received_so_far')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('type','Task')
				 ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));	 
			return $task->count();
		});

		$this->addExpression('total_tasks_assigned')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('type','Task')
                 ->addCondition('created_by_id',$q->getField('id'))
                 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
			return $task->count();
		});

		$this->addExpression('take_report_on_pending')->set(function($m,$q){
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

		$this->addExpression('check_submitted')->set(function($m,$q){
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
	}
}