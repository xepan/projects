<?php

namespace xepan\projects;

class Model_Formatted_Project extends \xepan\projects\Model_Project{
	function init(){
		parent::init();

		$this->addExpression('proposed_days')->set(function($m,$q){
			return $q->expr('DATEDIFF([0],[1])',[$m->getElement('ending_date'),$m->getElement('starting_date')]);
		});

		$this->addExpression('days_past')->set(function($m,$q){
			return $q->expr('DATEDIFF([0],[1])',["'".$this->app->now."'",$m->getElement('starting_date')]);
		});

		$this->addExpression('actual_days')->set(function($m,$q){
			// if !end_days return 0
			// date_diff (completed_on, start_date)
			return $q->expr("IF([0],DATEDIFF([0],[1]),DATEDIFF([2],[1]))",
											[
												$m->getElement('actual_completion_date'),
												$m->getElement('starting_date'),
												$m->getElement('ending_date')												
											]);
		});

		$this->addExpression('get_progress')->set(function($m,$q){
			return $q->expr('IF(IFNULL([0],false) AND IFNULL([1],false),1,0)',[$m->getElement('starting_date'),$m->getElement('ending_date')]);
		});

		$this->addExpression('progress')->set(function($m,$q){
			return $q->expr('IF([get_progress] = 0,
								0,
								if([status]="Completed", 
									ROUND([actual_days]/[proposed_days]*100),
									ROUND([days_past]/[proposed_days]*100)
								)
							)',
							[
								'get_progress'=>$m->getElement('get_progress'),
								'proposed_days'=> $m->getElement('proposed_days'),
								'days_past' => $m->getElement('days_past'),
								'actual_days' => $m->getElement('actual_days'),
								'status' => $m->getElement('status')
							]);
		});

		$this->addExpression('progress_class')->set(function($m,$q){
			return $m->dsql()->expr(
					"IF([0]>75,'progress-bar-danger',
						if([0]>50,'progress-bar-warning',
						if([0]>25,'progress-bar','progress-bar-success'
						)))",

					  [
						$m->getElement('progress'),
					  ]

					);
		});

		$this->addExpression('total_task')->set(function ($m){
			return  $m->add('xepan\projects\Model_Task')->addCondition('project_id',$m->getElement('id'))->count();
		});
		
		$this->addExpression('pending_task_count')->set($this->refSQL('xepan\projects\Task')->addCondition('status',['Pending'])->addCondition('project_id',$this->getElement('id'))->count())->sortable(true);
		
		$this->addExpression('completed_task_count')->set($this->refSQL('xepan\projects\Task')->addCondition('status',['Completed'])->addCondition('project_id',$this->getElement('id'))->count())->sortable(true);

		$this->addExpression('completed_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('pending_task_count'),$m->getElement('total_task')]);
		});

		$this->addExpression('self_task')->set($this->refSQL('xepan\projects\Task')->addCondition('assign_to_id',$this->app->employee->id)->addCondition('project_id',$this->getElement('id'))->count());

		$this->addExpression('self_pending_task')->set($this->refSQL('xepan\projects\Task')->addCondition('assign_to_id',$this->app->employee->id)->addCondition('project_id',$this->getElement('id'))->addCondition('status',['Pending'])->count());

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

		$this->addExpression('total_critical_task')->set(function ($m){

			return  $m->add('xepan\projects\Model_Task')->addCondition('project_id',$m->getElement('id'))->addCondition('priority','90')->count();
		});
		
		
		$this->addExpression('critical_pending_task_count')->set($this->refSQL('xepan\projects\Task')->addCondition('project_id',$this->getElement('id'))->addCondition('status',['Pending'])->addCondition('priority','90')->count());

		$this->addExpression('critical_completed_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('critical_pending_task_count'),$m->getElement('total_critical_task')]);
		});

		$this->addExpression('total_high_task')->set(function ($m){

			return  $m->add('xepan\projects\Model_Task')->addCondition('project_id',$m->getElement('id'))->addCondition('priority','75')->count();
		});
		
		
		$this->addExpression('high_pending_task_count')->set($this->refSQL('xepan\projects\Task')->addCondition('status',['Pending'])->addCondition('project_id',$this->getElement('id'))->addCondition('priority','75')->count());

		$this->addExpression('high_completed_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('high_pending_task_count'),$m->getElement('total_high_task')]);
		});


		$this->addExpression('total_medium_task')->set(function ($m){

			return  $m->add('xepan\projects\Model_Task')->addCondition('project_id',$m->getElement('id'))->addCondition('priority','50')->count();
		});
		
		
		$this->addExpression('medium_pending_task_count')->set($this->refSQL('xepan\projects\Task')->addCondition('status',['Pending'])->addCondition('project_id',$this->getElement('id'))->addCondition('priority','50')->count());

		$this->addExpression('medium_completed_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('medium_pending_task_count'),$m->getElement('total_medium_task')]);
		});

		$this->addExpression('total_low_task')->set(function ($m){

			return  $m->add('xepan\projects\Model_Task')->addCondition('project_id',$m->getElement('id'))->addCondition('priority','25')->count();
		});
		
		
		$this->addExpression('low_pending_task_count')->set($this->refSQL('xepan\projects\Task')->addCondition('status',['Pending'])->addCondition('project_id',$this->getElement('id'))->addCondition('priority','25')->count());

		$this->addExpression('low_completed_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('low_pending_task_count'),$m->getElement('total_low_task')]);
		});


		$this->addExpression('self_critical_task')->set($this->refSQL('xepan\projects\Task')->addCondition('assign_to_id',$this->app->employee->id)->addCondition('project_id',$this->getElement('id'))->addCondition('priority','90')->count());

		$this->addExpression('critical_self_pending_task')->set($this->refSQL('xepan\projects\Task')->addCondition('assign_to_id',$this->app->employee->id)->addCondition('status',['Pending'])->addCondition('project_id',$this->getElement('id'))->addCondition('priority','90')->count());

		$this->addExpression('critical_self_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('critical_self_pending_task'),$m->getElement('self_critical_task')]);
		});


		$this->addExpression('self_high_task')->set($this->refSQL('xepan\projects\Task')->addCondition('assign_to_id',$this->app->employee->id)->addCondition('project_id',$this->getElement('id'))->addCondition('priority','75')->count());

		$this->addExpression('high_self_pending_task')->set($this->refSQL('xepan\projects\Task')->addCondition('assign_to_id',$this->app->employee->id)->addCondition('status',['Pending'])->addCondition('project_id',$this->getElement('id'))->addCondition('priority','75')->count());

		$this->addExpression('high_self_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('high_self_pending_task'),$m->getElement('self_high_task')]);
		});

		$this->addExpression('self_medium_task')->set($this->refSQL('xepan\projects\Task')->addCondition('assign_to_id',$this->app->employee->id)->addCondition('project_id',$this->getElement('id'))->addCondition('priority','50')->count());

		$this->addExpression('medium_self_pending_task')->set($this->refSQL('xepan\projects\Task')->addCondition('assign_to_id',$this->app->employee->id)->addCondition('status',['Pending'])->addCondition('project_id',$this->getElement('id'))->addCondition('priority','50')->count());

		$this->addExpression('medium_self_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('medium_self_pending_task'),$m->getElement('self_medium_task')]);
		});

		$this->addExpression('self_low_task')->set($this->refSQL('xepan\projects\Task')->addCondition('assign_to_id',$this->app->employee->id)->addCondition('project_id',$this->getElement('id'))->addCondition('priority','25')->count());

		$this->addExpression('low_self_pending_task')->set($this->refSQL('xepan\projects\Task')->addCondition('assign_to_id',$this->app->employee->id)->addCondition('project_id',$this->getElement('id'))->addCondition('status',['Pending'])->addCondition('priority','25')->count());

		$this->addExpression('low_self_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('low_self_pending_task'),$m->getElement('self_low_task')]);
		});

	}
}