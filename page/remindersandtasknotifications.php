<?php

namespace xepan\projects;

class page_remindersandtasknotifications extends \xepan\base\Page{
	function init(){
		parent::init();

		$tasks = $this->add('xepan\projects\Model_Task');
		$tasks->addCondition([['set_reminder',true],['is_recurring',true]]);
		$tasks->addCondition([['is_reminded',false],['is_reminded',null]]);

		if(!$this->app->auth->model->isSuperUser()){
			$tasks->addCondition([['created_by_id',$this->app->employee->id],['assign_to_id',$this->app->employee->id],['notify_to',$this->app->employee->id],['notify_to','like','%'.$this->app->employee->id.',%'],['notify_to','like','%,'.$this->app->employee->id.'%']]);
		}

		$grid = $this->add('xepan\hr\Grid');
		$grid->setModel($tasks,['task_name','created_by','assign_to','type','set_reminder','remind_via','remind_value','remind_unit','notify_to','is_recurring','recurring_span']);
		$grid->addPaginator(50);
		
		$grid->addColumn('Button','remove_reminder');
		$grid->addColumn('Button','remove_recurring');
		$grid->addColumn('Button','remove_me');
		
		if($this->app->auth->model->isSuperUser()){
			$grid->addColumn('Button','remove_task');
		}
		
		if($id = $_GET['remove_reminder']){
			$nt = $tasks->newInstance()->load($id);
			$nt['set_reminder']=false;
			$nt->save();
			$grid->js()->reload()->execute();
		}

		if($id = $_GET['remove_recurring']){
			$nt = $tasks->newInstance()->load($id);
			$nt['is_recurring']=false;
			$nt->save();
			$grid->js()->reload()->execute();
		}

		if($id = $_GET['remove_task']){
			$nt = $tasks->load($id);
			$nt->delete();
			$grid->js()->reload()->execute();
		}

		if($id = $_GET['remove_me']){
			$nt = $tasks->newInstance()->load($id);
			$notify_arr = explode(",", $nt['notify_to']);
			foreach (array_keys($notify_arr, $this->app->employee->id) as $key) {
				unset($notify_arr[$key]);
			}
			$nt['notify_to'] = implode(",", $notify_arr);
			$nt->save();
			$grid->js()->reload()->execute();
		}
	}
}
