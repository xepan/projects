<?php

namespace xepan\projects;

class page_widget_todaystask extends \xepan\base\Page{
	function init(){
		parent::init();
		
		$employee_id = $this->app->stickyGET('employee_id');		

		$tabs = $this->add('Tabs');
		$tab1 = $tabs->addTab('Task Created');
		$tab2 = $tabs->addTab('Task Pending');
		$tab3 = $tabs->addTab('Task To Receive');
		$tab4 = $tabs->addTab('Task Submitted');
		$tab5 = $tabs->addTab('Task Completed');
		$tab6 = $tabs->addTab('Task Assigned');

		$task_created_m = $tab1->add('xepan\projects\Model_Task');
		$task_created_m->addCondition('type','Task');
		$task_created_m->addCondition('created_by_id',$employee_id);
		$task_created_m->addCondition('created_at','>=',$this->app->today);
		$grid_created = $tab1->add('xepan\hr\Grid');
		$grid_created->setModel($task_created_m,['task_name','status','created_at','created_by','assign_to']);
		$grid_created->add('View',null,'grid_buttons')->setHtml('<b>TASK CREATED</b>');
		$grid_created->addPaginator('10');

		$task_pending_m = $tab2->add('xepan\projects\Model_Task');
		$task_pending_m->addCondition('type','Task');
		$task_pending_m->addCondition('assign_to_id',$employee_id);
		$task_pending_m->addCondition('status','Pending');
		$grid_pending = $tab2->add('xepan\hr\Grid');
		$grid_pending->setModel($task_pending_m,['task_name','status','created_at','created_by','assign_to']);
		$grid_pending->add('View',null,'grid_buttons')->setHtml('<b>TASK PENDING</b>');
		$grid_pending->addPaginator('10');

		$task_toreceive_m = $tab3->add('xepan\projects\Model_Task');
		$task_toreceive_m->addCondition('type','Task');
		$task_toreceive_m->addCondition('assign_to_id',$employee_id);
		$task_toreceive_m->addCondition('status','Assigned');
		$grid_toreceive = $tab3->add('xepan\hr\Grid');
		$grid_toreceive->setModel($task_toreceive_m,['task_name','status','created_at','created_by','assign_to']);
		$grid_toreceive->add('View',null,'grid_buttons')->setHtml('<b>TASK To Receive</b>');
		$grid_toreceive->addPaginator('10');

		$task_submitted_m = $tab4->add('xepan\projects\Model_Task');
		$task_submitted_m->addCondition('type','Task');
		$task_submitted_m->addCondition('assign_to_id',$employee_id);
		$task_submitted_m->addCondition('status','Submitted');
		$grid_submitted = $tab4->add('xepan\hr\Grid');
		$grid_submitted->setModel($task_submitted_m,['task_name','status','created_at','created_by','assign_to']);
		$grid_submitted->add('View',null,'grid_buttons')->setHtml('<b>TASK SUBMITTED</b>');
		$grid_submitted->addPaginator('10');

		$task_completed_m = $tab5->add('xepan\projects\Model_Task');
		$task_completed_m->addCondition('type','Task');
		$task_completed_m->addCondition('assign_to_id',$employee_id);
		$task_completed_m->addCondition('status','Completed');
		$task_completed_m->addCondition('created_at','>=',$this->app->now);
		$grid_completed = $tab5->add('xepan\hr\Grid');
		$grid_completed->setModel($task_completed_m,['task_name','status','created_at','created_by','assign_to']);
		$grid_completed->add('View',null,'grid_buttons')->setHtml('<b>TASK COMPLETED</b>');
		$grid_completed->addPaginator('10');

		$task_assigned_m = $tab6->add('xepan\projects\Model_Task');
		$task_assigned_m->addCondition('type','Task');
		$task_assigned_m->addCondition('created_by_id',$employee_id);
		$task_assigned_m->addCondition('assign_to_id','<>',$employee_id);
		$task_assigned_m->addCondition('created_at','>=',$this->app->now);
		$grid_assigned = $tab6->add('xepan\hr\Grid');
		$grid_assigned->setModel($task_assigned_m,['task_name','status','created_at','created_by','assign_to']);
		$grid_assigned->add('View',null,'grid_buttons')->setHtml('<b>TASK ASSIGNED</b>');
		$grid_assigned->addPaginator('10');
	}
}