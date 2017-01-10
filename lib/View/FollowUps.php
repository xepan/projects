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
		$model_task->tryLoadBy('id',$task_id);

		if(!$model_task->loaded())
			return;
				
		$tabs = $this->add('Tabs');
		$tab1 = $tabs->addTab('Follow Up Task');
		$tab2 = $tabs->addTab('Communicaion Details');
		
		$contact_info = $tab1->add('Button');
		$contact_info->set('View Contact Information')->addClass('btn btn-info btn-block');

		if($model_task['related_id']){
			$tab3 = $tabs->addTabURL($this->api->url('xepan_marketing_leaddetails',['contact_id'=>$model_task['related_id']]),'Contact Detail');
			// $this->js()->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$model_task['related_id']]);
		}

		$task_view = $tab1->add('xepan\projects\View_Detail',['task_id'=>$task_id,'project_id'=>$project_id]);	

		$form = $tab2->add('Form');
		$form->setLayout('view\conversationfilter');
		$type_field = $form->addField('xepan\base\DropDown','communication_type');
		$type_field->setAttr(['multiple'=>'multiple']);
		$type_field->setValueList(['TeleMarketing'=>'TeleMarketing','Email'=>'Email','Support'=>'Support','Call'=>'Call','Newsletter'=>'Newsletter','SMS'=>'SMS','Personal'=>'Personal','Comment'=>'Comment']);
		$form->addField('search');
		$form->addSubmit('Filter')->addClass('btn btn-primary btn-block');
		
		$temp = ['Email','Support','Call','Newsletter','SMS','Personal','Comment'];
		$type_field->set($_GET['comm_type']?explode(",", $_GET['comm_type']):$temp)->js(true)->trigger('changed');

		$communication_view = $tab2->add('xepan\communication\View_Lister_Communication',['contact_id'=>$model_task['related_id']]);
		
		$model_communication = $this->add('xepan\communication\Model_Communication');
		$model_communication->addCondition(
										$model_communication->dsql()->andExpr()
									  	->where('to_id',$model_task['related_id'])
									  	->where('to_id','<>',null));
		$model_communication->setOrder('id','desc');
		
		if($_GET['comm_type']){			
			$model_communication->addCondition('communication_type',explode(",", $_GET['comm_type']));
		}

		if($search = $this->app->stickyGET('search')){			
			$model_communication->addExpression('Relevance')->set('MATCH(title,description,communication_type) AGAINST ("'.$search.'")');
			$model_communication->addCondition('Relevance','>',0);
 			$model_communication->setOrder('Relevance','Desc');
		}

		$communication_view->setModel($model_communication);
		$communication_view->add('Paginator',['ipp'=>10]);
		
		if($form->isSubmitted()){												
			$communication_view->js()->reload(['comm_type'=>$form['communication_type'],'search'=>$form['search']])->execute();
		}
	}
}