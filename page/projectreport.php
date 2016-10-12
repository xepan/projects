<?php

namespace xepan\projects;

class page_projectreport extends page_reportsidebar{
	public $title = "Project Report";

	function init(){
		parent::init();
		
		/*******************************************************************
		 GETTING VALUES FROM URL	
		********************************************************************/
		// $from_date = $this->app->stickyGET('from_date')?:$this->app->today;
		// $to_date = $this->app->stickyGET('to_date')?:$this->app->nextDate($this->app->today);
		
		/*******************************************************************
		 PROJECT MODEL AND EXPRESSIONS	
		********************************************************************/
		$project = $this->add('xepan\projects\Model_Project');

		$project->addExpression('Resources')->set(function($m,$q){
			return $q->expr("IFNULL([0],0)", [$m->refSQL('xepan\projects\Task')->_dsql()->del('fields')->field('count(distinct(created_by_id))')]);
		});

		// TOTAL ESTIMATE HOURS ALOTED IN PROJECT'S TASKS
		$project->addExpression('Estimate')->set(function($m,$q){
			return $q->expr('IFNULL([0],0)',[$m->refSQL('xepan\projects\Task')->sum('estimate_time')]);
		});

		// TOTAL HOURS ALLOTED 
		$project->addExpression('Alloted')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'));

			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['HOUR',$q->getField('starting_date'),$q->getField('deadline')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('diff_time')]));
		}); 

		// TOTAL HOURS CONSUMED 
		$project->addExpression('Consumed')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'));
			$task->addCondition('status','Completed');

			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['HOUR',$q->getField('starting_date'),$q->getField('updated_at')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('diff_time')]));
		}); 
		
		/*******************************************************************
	 	 FORM TO ENTER INFORMATION
		********************************************************************/
		// $form = $this->add('Form');
		// $form->addField('DatePicker','from_date')->set($this->app->today);
		// $form->addField('DatePicker','to_date')->set($this->app->today);
		// $form->addSubmit('Get Report')->addclass('btn btn-primary btn-sm btn-block');
		
		// GRID WILL BE ADDED ON THIS VIEW
		$view = $this->add('View');

		/*******************************************************************
		 ADDING GRID ON VIEW AND SETTING MODEL
		********************************************************************/
		$grid = $view->add('Grid');			
		$grid->setModel($project,['name','Resources','Estimate','Alloted','Consumed']);
		$grid->addQuickSearch(['name']);
		/*******************************************************************
		 HANDLING FORM SUBMISSION
		********************************************************************/
		// if($form->isSubmitted()){
		// 	$array = [
		// 				'from_date'=>$form['from_date'],
		// 				'to_date'=>$form['to_date'],
		// 			 ];
		// 	$view->js()->reload($array)->execute();
		// }
	}
}