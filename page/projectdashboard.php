<?php

namespace xepan\projects;

class page_projectdashboard extends \xepan\projects\page_sidemenu{
	public $title = "Dashboard";	
	function init(){
		parent::init();



		// Communications by staff 
     	$model = $this->add('xepan\hr\Model_Employee');
		
		$model->addExpression('pending_works')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task')
						->addCondition('assign_to_id',$q->getField('id'))
						->addCondition('created_by_id','<>',$q->getField('id'))
						->addCondition('status','Pending')
						->count();
		});

		$model->addExpression('please_receive')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task')
						->addCondition('assign_to_id',$q->getField('id'))
						->addCondition('created_by_id','<>',$q->getField('id'))
						->addCondition('status','Assigned')
						->count();
		});

		$model->addExpression('received_so_far')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task')
						->addCondition('assign_to_id',$q->getField('id'))
						->addCondition('created_by_id','<>',$q->getField('id'))
						->count();
		});

		$model->addExpression('total_tasks_assigned')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task')
						->addCondition('assign_to_id','<>',$q->getField('id'))
						->addCondition('created_by_id',$q->getField('id'))
						->count();
		});

		$model->addExpression('take_report_on_pending')->set(function($m,$q){
			return $this->add('xepan\projects\Model_Task')
						->addCondition('assign_to_id','<>',$q->getField('id'))
						->addCondition('created_by_id',$q->getField('id'))
						->addCondition('status',['Pending','Assigned','Submitted'])
						->count();
		});

     	$this->add('xepan\base\View_Chart',null,'Charts')
     		->setType('bar')
     		->setModel($model,'name',['pending_works','please_receive','received_so_far','total_tasks_assigned','take_report_on_pending'])
     		->setGroup([['received_so_far','total_tasks_assigned'],['pending_works','take_report_on_pending']])
     		// ->setGroup(['self_pending','given_tasks_pending'])
     		->setTitle('Staff Accountable System Use')
     		->addClass('col-md-12')
     		->rotateAxis()
     		;



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
		$this->add('xepan\base\View_Chart',null,'Charts',null)
     		->setType('bar')
     		->setModel($project,'name',['Estimate','Alloted','Consumed'])
     		->setGroup(['Estimate','Alloted','Consumed'])
     		->setTitle('Project Hour Consumption')
     		->addClass('col-md-8')
     		->rotateAxis();
	}

	function defaultTemplate(){
		return ['page\projectdashboard'];
	}
}