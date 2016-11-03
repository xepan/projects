<?php

namespace xepan\projects;

class View_FollowUps extends \View{
	public $task_id;
	public $project_id = null;

	function init(){
		parent::init();
		
		$task_id = $this->task_id;
		$project_id = $this->project_id;

		$model_task = $this->add('xepan\projects\Model_Task');
		$model_task->addExpression('contact_id')->set(function($m,$q){
			$comm = $this->add('xepan\communication\Model_Communication');
			$comm->addCondition('id',$m->getElement('related_id')); 
			$comm->setLimit(1);
			return $comm->fieldQuery('to_id'); 
		});

		$model_task->tryLoadBy('id',$task_id);

		if(!$model_task->loaded())
			return;
				
		$tabs = $this->add('Tabs');
		$tab1 = $tabs->addTab('Follow Up Task');
		$tab2 = $tabs->addTab('Communicaion Details');
		
		$task_view = $tab1->add('xepan\projects\View_Detail',['task_id'=>$task_id,'project_id'=>$project_id]);	
		$communication_view = $tab2->add('xepan\communication\View_Lister_Communication',['contact_id'=>$model_task['contact_id']]);
		
		$model_communication = $this->add('xepan\communication\Model_Communication');
		$model_communication->addCondition(
										$model_communication->dsql()->andExpr()
									  	->where('to_id',$model_task['contact_id'])
									  	->where('to_id','<>',null));
		$model_communication->setOrder('id','desc');
		$communication_view->setModel($model_communication);
		$communication_view->add('Paginator',['ipp'=>10]);
	}
}