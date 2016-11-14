<?php

namespace xepan\projects;

class Model_Widget_HourConsumption extends \xepan\projects\Model_Project{
	public $start_date;
	public $end_date;

	function init(){
		parent::init();

		$this->addExpression('Estimate')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'))
				 ->addCondition('created_at','>=',$this->start_date)
				 ->addCondition('created_at','<=',$this->end_date);	
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('estimate_time')]));
		});

		$this->addExpression('Alloted')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'))
				 ->addCondition('created_at','>=',$this->start_date)
				 ->addCondition('created_at','<=',$this->end_date);	

			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['HOUR',$q->getField('starting_date'),$q->getField('deadline')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('diff_time')]));
		}); 

		$this->addExpression('Consumed')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'));
			$task->addCondition('status','Completed')
				 ->addCondition('created_at','>=',$this->start_date)
				 ->addCondition('created_at','<=',$this->end_date);	

			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['HOUR',$q->getField('starting_date'),$q->getField('updated_at')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('diff_time')]));
		}); 
		
		$this->addCondition([['Estimate','>',0],['Alloted','>',0],['Consumed','>',0]]);
	}
} 