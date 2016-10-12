<?php

namespace xepan\projects;

class page_projectdashboard extends \xepan\projects\page_sidemenu{
	public $title = "Dashboard";	
	function init(){
		parent::init();

		$project = $this->add('xepan\projects\Model_Project');

		$project->addExpression('Estimate')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'));
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('estimate_time')]));
		});

		$project->addExpression('Alloted')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'));

			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['HOUR',$q->getField('starting_date'),$q->getField('deadline')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('diff_time')]));
		}); 

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
		
		$project->addCondition([['Estimate','>',0],['Alloted','>',0],['Consumed','>',0]]);
		$this->add('xepan\base\View_Chart',null,null,null)
     		->setType('bar')
     		->setModel($project,'name',['Estimate','Alloted','Consumed'])
     		->setGroup(['Estimate','Alloted','Consumed'])
     		->setTitle('Project Hour Consumption')
     		->addClass('col-md-8')
     		->rotateAxis();
	}

	// function defaultTemplate(){
	// 	return ['page\dashboard'];
	// }
}