<?php

namespace xepan\projects;

class View_MiniTask extends \View{

	function init(){
		parent::init();

		$this->addClass('xepan-mini-task');
		$this->js('reload')->reload();

		$model_view = $this->add('xepan\base\View_ModelPopup');
		$model_view->setTitle("INSTANT TASK FEED");
		
		$model_view->add('xepan\projects\View_InstantTaskFeed');	
		$task_list = $this->add('xepan\projects\View_TaskList',['no_records_message'=>'You Are Sitting Idle'],null,['view\minitask']);
		$task_list
				->setAttr("data-toggle","modal")
				->setAttr( "data-target",".".$model_view->name);
		
		$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
		$model_timesheet->addCondition('employee_id',$this->app->employee->id);
		$model_timesheet->addCondition('endtime',null);
		$model_timesheet->tryLoadAny();

		$model_task = $this->add('xepan\projects\Model_Formatted_Task');
		$model_task->addCondition('id',$model_timesheet->fieldQuery('task_id'));				
		$model_task->tryLoadAny();
		

		$task_list->setModel($model_task);
	}

}