<?php

namespace xepan\projects;

class View_MiniTask extends \View{

	function init(){
		parent::init();

		$this->addClass('xepan-mini-task');
		$this->js('reload')->reload();

		$task_list = $this->add('xepan\projects\View_TaskList',null,null,['view\minitask']);

		$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
		$model_timesheet->addCondition('employee_id',$this->app->employee->id);
		$model_timesheet->setOrder('starttime','desc');
		$model_timesheet->setLimit(1);
		$model_timesheet->tryLoadAny();

		$model_task = $this->add('xepan\projects\Model_Formatted_Task');
		$model_task->addCondition('id',$model_timesheet->fieldQuery('task_id'));				
		$model_task->tryLoadAny();
		

		$task_list->setModel($model_task);

		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$p->add('xepan\projects\View_InstantTaskFeed');			
		});

		$this->js('click')->univ()->dialogURL("INSTANT TASK FEED",$this->api->url($vp->getURL()))->_selector('.instant-task-feed');

	}

}