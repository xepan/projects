<?php

namespace xepan\projects;

class Model_Formatted_Project extends \xepan\projects\Model_Project{
	function init(){
		parent::init();

		$this->addExpression('total_task')->set(function ($m){

			return  $m->add('xepan\projects\Model_Task')->addCondition('project_id',$_GET['project_id'])->count();
		});
		
		$this->addExpression('pending_task_count')->set($this->refSQL('xepan\projects\Task')->addCondition('status',['Pending'])->count());

		$this->addExpression('completed_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('pending_task_count'),$m->getElement('total_task')]);
		});

		$this->addExpression('self_task')->set($this->refSQL('xepan\projects\Task')->addCondition('employee_id',$this->app->employee->id)->count());

		$this->addExpression('self_pending_task')->set($this->refSQL('xepan\projects\Task')->addCondition('employee_id',$this->app->employee->id)->addCondition('status',['Pending'])->count());

		$this->addExpression('self_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('self_pending_task'),$m->getElement('self_task')]);
		});

		$this->addExpression('self_color')->set(function($m){
			return $m->dsql()->expr(
					"IF([0]>75,'success',
						if([0]>50,'warning',
						if([0]>25,'info','danger'
						)))",

					  [
						$m->getElement('self_percentage'),
					  ]

					);
		});

		$this->addExpression('color')->set(function($m){
			return $m->dsql()->expr(
					"IF([0]>75,'success',
						if([0]>50,'warning',
						if([0]>25,'info','danger'
						)))",

					  [
						$m->getElement('completed_percentage'),
					  ]

					);
		});

	}
}