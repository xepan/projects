<?php
namespace xepan\projects;

class View_InstantTaskFeed extends \View{
	function init(){
		parent::init();

		// $this->js(true)->_load('select2.min')->_css('libs/select2');

		$model_task = $this->add('xepan\projects\Model_Task');
		$model_project = $this->add('xepan\projects\Model_Project');
		$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
		
		$form = $this->add('Form',null,'form');
		$project_field = $form->addField('DropDown','project');
		$project_field->setModel($model_project);

		$task_field = $form->addField('xepan\base\DropDown','task');
		$task_field->validate_values = false;
		
		$form->addField('text','remark');
		$time_field = $form->addField('TimePicker','time');
		$time_field->options=['showMeridian'=>false];
		$form->addSubmit('Start');
		
		if($_GET[$this->name]){
			$results = [];
			$task_list_m = $this->add('xepan\projects\Model_Task');


			$task_list_m->addExpression('Relevance')->set('MATCH(task_name, description) AGAINST ("'.$_GET['q'].'" IN NATURAL LANGUAGE MODE)');
			$task_list_m->addCondition('Relevance','>',0);
	 		$task_list_m->setOrder('Relevance','Desc');
			$task_list_m->setLimit(20);


			foreach ($task_list_m as $task) {
				$results[] = ['id'=>$task->id,'text'=>$task['task_name']];
			}

			echo json_encode(
				[
					"results" => $results,
					"more"=>false	
				]
				);
			exit;
		}

		$task_field->select_menu_options = 
			[	
				'width'=>'100%',
				'tags'=>true,
				// 'tokenSeparators'=>["\t","\n\r",","],
				'ajax'=>[
					'url' => $this->api->url(null,[$this->name=>true])->getURL(),
					'data'=>$task_field->js(null,'return {q: $("#'.$task_field->name.'").select2("val"), project: $("#'.$project_field->name.'").select2("val")};')->_enclose(),
					'dataType'=>'json'
				]
			];

		if($form->isSubmitted()){
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
			
			if(!is_numeric($form['task'])){
				$model_task->addCondition('employee_id',$this->app->employee->id);
				$model_task['task_name'] = $form['task'];
				$model_task['project_id'] = $form['project'];
				$model_task['description'] = $form['remark'];
				$model_task->save();

				$model_timesheet->addCondition('employee_id',$this->app->employee->id);
				$model_timesheet->addCondition('task_id',$model_task->id);
				$model_timesheet['starttime'] = $this->app->now;
				$model_timesheet->save();
				return;
			}

			$model_timesheet->addCondition('employee_id',$this->app->employee->id);
			$model_timesheet->addCondition('task_id',$form['task']);
			$model_timesheet['remark'] = $form['remark'];
			$model_timesheet['starttime'] = $this->app->now;
			$model_timesheet->save();
			return;
		}
	}
	function defaultTemplate(){
		return['view/instanttaskfeed'];
	}
}