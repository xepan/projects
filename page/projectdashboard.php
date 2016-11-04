<?php

namespace xepan\projects;

class page_projectdashboard extends \xepan\projects\page_sidemenu{
	public $title = "Dashboard";	
	function init(){
		parent::init();

		$this->start_date = $start_date = $_GET['start_date']?:date("Y-m-d", strtotime('-29 days', strtotime($this->app->today))); 		
		$this->end_date = $end_date = $_GET['end_date']?:$this->app->today;
				
		// // HEADER FORM
		$form = $this->add('Form',null,'form_layout');
		$fld = $form->addField('DateRangePicker','period')
                ->setStartDate($start_date)
                ->setEndDate($end_date);

        $this->end_date = $this->app->nextDate($this->end_date);
		$form->addSubmit("Filter")->addClass('btn btn-primary');
		
		if($form->isSubmitted()){
			$form->app->redirect($this->app->url(null,['start_date'=>$fld->getStartDate()?:0,'end_date'=>$fld->getEndDate()?:0]));
		}

		// Communications by staff 
     	$model = $this->add('xepan\hr\Model_Employee');
     	$model->addCondition('status','Active');

		$model->addExpression('pending_works')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition('status','Pending')
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
			return $task->count();
		});

		$model->addExpression('please_receive')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
      	         ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition('status','Assigned')
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
            return $task->count();
		});

		$model->addExpression('received_so_far')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id',$q->getField('id'))
				 ->addCondition('created_by_id','<>',$q->getField('id'))
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));	 
			return $task->count();
		});

		$model->addExpression('total_tasks_assigned')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
                 ->addCondition('created_by_id',$q->getField('id'))
                 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
			return $task->count();
		});

		$model->addExpression('take_report_on_pending')->set(function($m,$q){
			$task  = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
                 ->addCondition('created_by_id',$q->getField('id'))
				 ->addCondition('status',['Pending','Assigned'])
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
			return $task->count();
		});

		$model->addExpression('check_submitted')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('assign_to_id','<>',$q->getField('id'))
				 ->addCondition('created_by_id',$q->getField('id'))
				 ->addCondition('status','Submitted')
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));
			return $task->count();
		});

     	$this->add('xepan\base\View_Chart',null,'Charts')
     		->setType('bar')
     		->setModel($model,'name',['pending_works','please_receive','received_so_far','total_tasks_assigned','take_report_on_pending','check_submitted'])
     		->setGroup([['received_so_far','total_tasks_assigned'],['pending_works','take_report_on_pending']])
     		// ->setGroup(['self_pending','given_tasks_pending'])
     		->setTitle('Staff Accountable System Use')
     		->addClass('col-md-12')
     		// ->rotateAxis()
     		;



		$project = $this->add('xepan\projects\Model_Project');

		$project->addExpression('Estimate')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'))
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));	
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('estimate_time')]));
		});

		$project->addExpression('Alloted')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'))
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));	

			$task->addExpression('diff_time')->set(function($m,$q){
				return $q->expr('TIMESTAMPDIFF([0],[1],[2])',
					['HOUR',$q->getField('starting_date'),$q->getField('deadline')]);
			});
			return $task->_dsql()->del('fields')->field($q->expr('sum([0])',[$task->getElement('diff_time')]));
		}); 

		$project->addExpression('Consumed')->set(function($m,$q){
			$task = $this->add('xepan\projects\Model_Task');
			$task->addCondition('project_id',$m->getElement('id'));
			$task->addCondition('status','Completed')
				 ->addCondition($task->dsql()->andExpr()
				 ->where('created_at','>=',$this->start_date)
				 ->where('created_at','<=',$this->end_date));	

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