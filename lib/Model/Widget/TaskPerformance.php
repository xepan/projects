<?php

namespace xepan\projects;

class Model_Widget_TaskPerformance extends \xepan\projects\Model_Task{
	public $start_date;
	public $end_date;
	public $employee;
	function init(){
		parent::init();

		$this->addExpression('average_receiving_time')->set(function($m,$q){				
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('starting_date','>',$this->start_date)
				 ->addCondition('starting_date','<',$this->end_date)
				 ->addCondition('type','Task');	
			if(!empty($this->employee)){				
				$task->addCondition('created_by_id','<>',$this->employee)
				 	 ->addCondition('assign_to_id',$this->employee);
			}
				 
			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['MINUTE',$q->getField('created_at'),$q->getField('received_at')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('avg([0])',[$task->getElement('diff_time')]));
		}); 

		$this->addExpression('average_submission_time')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('starting_date','>',$this->start_date)
				 ->addCondition('starting_date','<',$this->end_date)
				 ->addCondition('type','Task');	
			if(!empty($this->employee)){
				$task->addCondition('created_by_id','<>',$this->employee)
				 	 ->addCondition('assign_to_id',$this->employee);
			}	 

			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['MINUTE',$q->getField('created_at'),$q->getField('submitted_at')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('avg([0])',[$task->getElement('diff_time')]));
		}); 

		$this->addExpression('average_reacting_time')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('starting_date','>',$this->start_date)
				 ->addCondition('starting_date','<',$this->end_date)
				 ->addCondition('type','Task');	
			
			if(!empty($this->employee)){
				$task->addCondition('created_by_id',$this->employee)
				 	 ->addCondition('assign_to_id','<>',$this->employee);
			}

			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['MINUTE',$q->getField('submitted_at'),$q->getField('completed_at')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('avg([0])',[$task->getElement('diff_time')]));
		}); 
	}
}