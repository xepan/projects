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

		$data=[];
		if($model_task['is_running']){
			$this->template->trySet('icon','fa fa-stop');
			$data['action'] == 'stop';
		}else{			
			$this->template->trySet('icon','fa fa-play');
			$data['action'] == 'start';
		}

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

			if($data['action']=='start'){					
				$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
				$model_timesheet['task_id'] = $data['id'];
				$model_timesheet['employee_id'] = $this->app->employee->id;
				$model_timesheet['starttime'] = $this->app->now;
				$model_timesheet->save();

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