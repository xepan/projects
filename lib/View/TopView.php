<?php

namespace xepan\projects;

class View_TopView extends \View{
	function init(){
		parent::init();

	}

	function setModel($model){
		$model->addExpression('total_task')->set($model->refSQL('xepan\projects\Model_Task')->count());
		$model->addExpression('pending_task_count')->set($model->refSQL('xepan\projects\Model_Task')->addCondition('status',['Pending'])->count());

		$model->addExpression('completed_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('pending_task_count'),$m->getElement('total_task')]);
		});

		$model->addExpression('self_task')->set($model->refSQL('xepan\projects\Model_Task')->addCondition('employee_id',$this->app->employee->id)->count());

		$model->addExpression('self_pending_task')->set($model->refSQL('xepan\projects\Model_Task')->addCondition('employee_id',$this->app->employee->id)->addCondition('status',['Pending'])->count());

		$model->addExpression('self_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('self_pending_task'),$m->getElement('self_task')]);
		});

		$model->addExpression('self_color')->set(function($m){
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

		$model->addExpression('color')->set(function($m){
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

		$m = parent::setModel($model);
		
		return $m;
	}

	function defaultTemplate(){
		return['view\topview'];
	}
}