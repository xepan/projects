<?php

namespace xepan\projects;

class View_MiniTask extends \View{
	function init(){
		parent::init();

		$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
		$model_timesheet->addCondition('employee_id',$this->app->employee->id);
		$model_timesheet->setOrder('starttime','desc');
		$model_timesheet->setLimit(1);

		$model_task = $this->add('xepan\projects\Model_Formatted_Task');
		$model_task->addCondition('id',$model_timesheet->fieldQuery('task_id'));				
		$model_task->tryLoadAny();
		$this->setModel($model_task);

		$this->on('click','.current_task_btn',function($js,$data){
		
		$timesheet = $this->add('xepan\projects\Model_Timesheet');
		$timesheet->addCondition('employee_id',$this->app->employee->id);
		$timesheet->setOrder('starttime','desc');
		$timesheet->tryLoadAny();

		if($timesheet->loaded()){			
			if(!$timesheet['endtime']){
				$timesheet['endtime'] = $this->app->now;
				$timesheet->save();
			}
		}

		if($data['action']=='start'){

			$model_timesheet1 = $this->add('xepan\projects\Model_Timesheet');
				
			$model_timesheet1['task_id'] = $data['id'];
			$model_timesheet1['employee_id'] = $this->app->employee->id;
			$model_timesheet1['starttime'] = $this->app->now;
			$model_timesheet1->save();

			return [
					$this->js()->_selector('.current_task_btn')->removeClass('fa-stop')->addClass('fa-play'),
					$this->js()->_selector('.dd3-content')->removeClass('alert alert-info'),
					$js->removeClass('fa-play')->addClass('fa-stop')->data('action','stop'),
					$this->js()->_selector('.dd3-content[data-id='.$data['id'].']')->addClass('alert alert-info'),
				];
		}

		return $js->removeClass('fa-stop')->addClass('fa-play')->data('action','start');	


	});



		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$p->add('xepan\projects\View_InstantTaskFeed');			
		});

		$this->js('click')->univ()->dialogURL("INSTANT TASK FEED",$this->api->url($vp->getURL()))->_selector('.instant-task-feed');
	}

	function defaultTemplate(){
		return ['view\minitask'];
	}
}