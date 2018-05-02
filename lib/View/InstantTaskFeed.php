<?php
namespace xepan\projects;

class View_InstantTaskFeed extends \View{
	function init(){
		parent::init();
		
		/*****************************************************************************
		 Showing pending tasks on 'pending task' tab
		******************************************************************************/
		$model_pending_task = $this->add('xepan\projects\Model_Task');
		$model_pending_task->addCondition('assign_to_id',$this->app->employee->id);
		$model_pending_task->addCondition('status','Pending');
		$model_pending_task->addCondition('is_regular_work',false);
		$model_pending_task->addCondition('type','Task');
		
		$pending_task_view = $this->add('xepan\projects\View_TaskList',['no_records_message'=>'No pending task found'],'pending_tasks');
		$pending_task_view->setModel($model_pending_task);
		$pending_task_view->add('xepan\hr\Controller_ACL',['action_btn_group'=>'xs']);
		$pending_task_view->addPaginator(5);
		$pending_task_view->addQuickSearch(['task_name']);
		$pending_task_view->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		/***********************************************************************
		 Form to add new task and to select project
		************************************************************************/
		$model_project = $this->add('xepan\projects\Model_Project');
		$form = $this->add('Form',null,'project_form');
		$form->add('xepan\base\Controller_FLC')
		->showLables(true)
		->makePanelsCoppalsible(true)
		->layout([
				'project~Project, If you want this new task to be in that project'=>'New Task Create and Run~c1~12~closed',
				'new_task'=>'c2~12',
				'starting_date'=>'c3~4',
				'deadline'=>'c4~4',
				'time'=>'c5~4~Working on it today since',
				'FormButtons~'=>'c6~4'
			]);

		$project_field = $form->addField('DropDown','project');
		$project_field->setModel($model_project);
		$project_field->setEmptyText('Select a project or leave unchanged to create generic task');
		$new_task_field = $form->addField('new_task')->validate('required');
		$form->addField('DateTimePicker','starting_date')->set($this->app->now);		
		$form->addField('DateTimePicker','deadline')->set($this->app->nextDate($this->app->now));
		$time_field = $form->addField('TimePicker','time','Working on it since');
			$time_field
				->setOption('showMeridian',false)
				->setOption('minuteStep',1)
				->setOption('showSeconds',true);
		$form->addSubmit('Create & Run');

		/****************************************************************
		 showing Today's tasks
		*****************************************************************/ 
		$model_task = $this->add('xepan\projects\Model_Task');
		$model_task->addCondition([['starting_date','>',$this->app->today],['is_regular_work',true],['status','Inprogress']]);
		$model_task->addCondition('assign_to_id',$this->app->employee->id);
		$model_task->addCondition('type','Task');

		if($project_id = $this->app->stickyGET('project_id')){
			$model_task->addCondition('project_id',$project_id);
		} 

		$task_view = $this->add('xepan\projects\View_TaskList',['no_records_message'=>'No task found, try adding new task'],'task');
		$task_view->setModel($model_task);
		$task_view->add('xepan\hr\Controller_ACL',['action_btn_group'=>'xs']);
		$task_view->addPaginator(25);
		$task_view->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);

		/************************************************************************************
		 Handling form event and submission 
		*************************************************************************************/
		$project_field->js('change',$task_view->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$task_view->name]),'project_id'=>$project_field->js()->val()]));
		
		if($form->isSubmitted()){
			// If user just want to run previously added task then exit from if
			if($form['new_task'] ==='')
				exit;

			// forming starting 'date and time' from time added by user
			$timestamp = $this->app->today;
			$timestamp .= ' '.$form['time'];
			$starting_time = date('Y-m-d H:i:s',strtotime($timestamp));

			// checking previouly added timesheet entries for clash
			// ToDo

			// ending previous running task if any
			$model_close_timesheet = $this->add('xepan\projects\Model_Timesheet');
			$model_close_timesheet->addCondition('employee_id',$this->app->employee->id);
			$model_close_timesheet->addCondition('endtime',null);
			$model_close_timesheet->tryLoadAny();			

			if($model_close_timesheet->loaded()){
				if(!$model_close_timesheet['endtime']){
					$model_close_timesheet['endtime'] = $this->app->now;
					$model_close_timesheet->saveAndUnload();
				}
			}
			
			// Adding new task
			$model_new_task = $this->add('xepan\projects\Model_Task');
			$model_new_task->addCondition('assign_to_id',$this->app->employee->id);
			$model_new_task['task_name'] = $form['new_task'];
			$model_new_task['project_id'] = $form['project'];
			$model_new_task['starting_date'] = $this->app->now;
			$model_new_task['deadline'] = $form['deadline'];
			$model_new_task['type'] = 'Task';
			$model_new_task->save();

			// starting new task
			$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
			$model_timesheet->addCondition('employee_id',$this->app->employee->id);
			$model_timesheet->addCondition('task_id',$model_new_task->id);
			$model_timesheet['starttime'] = $starting_time;
			$model_timesheet->save();

			$js = [
				  	$this->js()->univ()->successMessage('Task started'),
					$task_view->js()->reload(),
					$this->js()->_selector('.xepan-mini-task')->trigger('reload'),
				  ];
			return $form->js(null,$js)->reload()->execute(); 				
		}
	}

	function defaultTemplate(){
		return['view/instanttaskfeed'];
	}
}