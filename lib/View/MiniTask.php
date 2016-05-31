<?php

namespace xepan\projects;

class View_MiniTask extends \View{
	function init(){
		parent::init();

		$this->js(true)->_load('timer.jquery');

		$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
		$model_timesheet->addCondition('employee_id',$this->app->employee->id);
		$model_timesheet->setOrder('starttime','desc');
		$model_timesheet->setLimit(1);
		$model_timesheet->tryLoadAny();

		$model_task = $this->add('xepan\projects\Model_Formatted_Task');
		$model_task->addCondition('id',$model_timesheet->fieldQuery('task_id'));				
		$model_task->tryLoadAny();
		$this->setModel($model_task);
		
		if($model_task['is_running']){			
			$this->template->trySet('icon','fa fa-stop');
			$this->template->trySet('running-task','text-danger');
			$this->template->trySet('possible_action','stop');
			$this->js(true)->_selector('.current_task_btn .duration')->timer(['seconds'=>$model_timesheet['duration']]);
		}else{			
			$this->template->trySet('icon','fa fa-play');
			$this->template->trySet('running-task','text-success');
			$this->template->trySet('possible_action','start');
		}

		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$p->add('xepan\projects\View_InstantTaskFeed');			
		});


		$this->on('click','.current_task_btn',function($js,$data){
			
			$model_close_timesheet = $this->add('xepan\projects\Model_Timesheet');

			$model_close_timesheet->addCondition('employee_id',$this->app->employee->id);
			$model_close_timesheet->setOrder('starttime','desc');
			$model_close_timesheet->tryLoadAny();

			if($model_close_timesheet->loaded()){
				if(!$model_close_timesheet['endtime']){
					$model_close_timesheet['endtime'] = $this->app->now;
					$model_close_timesheet->save();
				}
			}

			if($data['action']==='start'){	
											
				$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
				$model_timesheet['task_id'] = $data['id'];
				$model_timesheet['employee_id'] = $this->app->employee->id;
				$model_timesheet['starttime'] = $this->app->now;
				$model_timesheet->save();
				return [
						$this->js()->_selector('.current_task_btn')->removeClass('fa-play')->addClass('fa-stop'),
						$this->js()->_selector('.current_task_btn .duration')->timer(['seconds'=>$model_timesheet['duration']]),
						$js->data('action','stop'), // next possible_action
					];
			}

			return [	
						$this->js()->_selector('.current_task_btn')->removeClass('fa-stop')->addClass('fa-play'),
						$this->js()->_selector('.current_task_btn .duration')->timer('remove'),
						$js->data('action','start') // next possible_action
					];

		});


		$this->js('click')->univ()->dialogURL("INSTANT TASK FEED",$this->api->url($vp->getURL()))->_selector('.instant-task-feed');

	}

	function defaultTemplate(){
		return ['view\minitask'];
	}
}