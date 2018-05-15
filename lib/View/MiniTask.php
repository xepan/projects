<?php

namespace xepan\projects;

class View_MiniTask extends \View{

	function init(){
		parent::init();

		$this->addClass('xepan-mini-task');
		$this->js('reload')->reload();

		// $model_view = $this->add('xepan\base\View_ModelPopup');
		// $model_view->setTitle("INSTANT TASK FEED");
		
		// $model_view->add('xepan\projects\View_InstantTaskFeed');	
		$task_list = $this->add('xepan\projects\View_TaskList',['no_records_message'=>'You Are Sitting Idle'],null,['view\minitask']);
		// $task_list
		// 		->setAttr("data-toggle","modal")
		// 		->setAttr( "data-target",".".$model_view->name);
		
		$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
		$model_timesheet->addCondition('employee_id',$this->app->employee->id);
		$model_timesheet->addCondition('endtime',null)->setLimit(1);
		$model_timesheet->tryLoadAny();

		$model_task = $this->add('xepan\projects\Model_Formatted_Task');
		$model_task->addCondition('id',$model_timesheet->fieldQuery('task_id'));		

		$task_list->setModel($model_task);

		// $task_list->js('click')->_selector('.xepan-mini-task')->univ()->frameURL('INSTANT TASK FEED',[$this->api->url($vp->getURL())]);
		$task_list->js('click')->_selector('.xepan-mini-task')->univ()->frameURL('What are you doing now?',[$this->app->url('xepan_projects_instanttaskfeed')]);

		$force_to_fill_sitting_ideal = $this->recall('force_to_fill_sitting_ideal',null);
		
		
		if($force_to_fill_sitting_ideal === NULL ){

			$config_m = $this->add('xepan\projects\Model_Config_ReminderAndTask');
			$config_m->tryLoadAny();

			$force_to_fill_sitting_ideal = false;
			if($config_m['force_to_fill_sitting_ideal'] && (trim($config_m['for_selected_posts'])=='' || in_array($this->app->employee['post_id'], explode(",", $config_m['for_selected_posts']))) ){
				$force_to_fill_sitting_ideal = $config_m['repeate_check_in_seconds']?$config_m['repeate_check_in_seconds']:60;
			}
			$this->memorize('force_to_fill_sitting_ideal',$force_to_fill_sitting_ideal);
		}



		if(!$task_list->running_task_id && $force_to_fill_sitting_ideal){

			$task_list->js(true,"sitting_ideal_interval = setInterval(function(){\$.univ().notify('Sitting Ideal ?','You looks sitting ideal, please tell what you are doing !','success',true,null,true,'warning');},".($force_to_fill_sitting_ideal?($force_to_fill_sitting_ideal*1000):60000).")");
			$task_list->js(true)->_selector('.xepan-mini-task')->univ()->frameURL('What are you doing now?',[$this->app->url('xepan_projects_instanttaskfeed')]);
		}else{
			$task_list->js(true,'if(typeof sitting_ideal_interval != "undefined") clearInterval(sitting_ideal_interval)');
		}
	}
}