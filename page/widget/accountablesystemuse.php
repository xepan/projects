<?php

namespace xepan\projects;

class page_widget_accountablesystemuse extends \xepan\base\Page{
	function init(){
		parent::init();

		$x_axis = $this->app->stickyGET('x_axis');
		$details = $this->app->stickyGET('details');
		$details = json_decode($details,true);
		$start_date = $this->app->stickyGET('start_date');
		$end_date = $this->app->stickyGET('end_date');
		
		$task_m = $this->add('xepan\projects\Model_Task');
		$task_m->addCondition('type','Task')
			   ->addCondition('created_at','>=',$start_date)
			   ->addCondition('created_at','<',$this->app->nextDate($end_date));
		
		switch ($details['name']) {
			case 'pending_works':			
			   $task_m->addCondition($task_m->dsql()->expr('("[0]"=[1] OR ("[0]"=[2] AND [3] is null ))',[
			   		$x_axis,
			   		$task_m->getElement('assign_to'),
			   		$task_m->getElement('created_by'),
			   		$task_m->getElement('assign_to_id')
			   	]))
			   ->addCondition('status','Pending');
				break;
			case 'please_receive':
				$task_m->addCondition($task_m->dsql()->expr('("[0]"=[1] OR ("[0]"=[2] AND [3] is null))',[
					   		$x_axis,
					   		$task_m->getElement('assign_to'),
					   		$task_m->getElement('created_by'),
					   		$task_m->getElement('assign_to_id')
					   	]))
			    ->addCondition('status','Assigned');
				break;
			case 'received_so_far':
				$task_m->addCondition($task_m->dsql()->expr('("[0]"=[1] OR ("[0]"=[2] AND [3] is null ))',[
					   		$x_axis,
					   		$task_m->getElement('assign_to'),
					   		$task_m->getElement('created_by'),
					   		$task_m->getElement('assign_to_id')
					   	]));
				break;
			case 'total_tasks_assigned':
				$task_m->addCondition($task_m->dsql()->expr('("[0]"=[1] AND ("[0]"<>[2] AND [3] is not null))',[
					   		$x_axis,
					   		$task_m->getElement('created_by'),
					   		$task_m->getElement('assign_to'),
					   		$task_m->getElement('assign_to_id')
					   	]));
				break;
			case 'take_report_on_pending':
				$task_m->addCondition($task_m->dsql()->expr('("[0]"=[1] AND ("[0]"<>[2] AND [3] is not null))',[
					   		$x_axis,
					   		$task_m->getElement('created_by'),
					   		$task_m->getElement('assign_to'),
					   		$task_m->getElement('assign_to_id')
					   	]))
					   ->addCondition('status',['Assigned','Pending']);
				break;
			case 'check_submitted':
				$task_m->addCondition($task_m->dsql()->expr('("[0]"=[1] AND ("[0]"<>[2] AND [3] is not null))',[
					   		$x_axis,
					   		$task_m->getElement('created_by'),
					   		$task_m->getElement('assign_to'),
					   		$task_m->getElement('assign_to_id')
					   	]))
					   ->addCondition('status','Submitted');
				break;
			default:
				break;
		}

		$task_v = $this->add('xepan\projects\View_TaskList',['del_action_wrapper'=>true]);
		$task_v->setModel($task_m);
		$task_v->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','image_field'=>'created_by_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);	   
	}
}