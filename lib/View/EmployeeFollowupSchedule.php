<?php


namespace xepan\projects;

class View_EmployeeFollowupSchedule extends \View {
	public $employee_field;
	public $date_field;
	public $employee_only;
	public $follow_type_field;
	
	function init(){
		parent::init();

		$this->setAttr('data-shortname','existing_schedule');

		$pass_in_get=[];
		if($this->employee_field) $pass_in_get['employee_field'] = $this->employee_field->name;
		if($this->date_field) $pass_in_get['date_field'] = $this->date_field->name;
		if($this->employee_only) $pass_in_get['employee_only'] = $this->employee_only;
		if($this->follow_type_field) $pass_in_get['follow_type_field'] = $this->follow_type_field->name;
		
		$btn = $this->add('Button')->set('Schedule');
		$btn->js('click')->univ()->frameURL('Schedule',$this->app->url('xepan_projects_taskscalendar',$pass_in_get),['width'=>$this->js()->width()->_selector('window')]);
	}

	// function init(){
	// 	parent::init();

	// 	$emp_id = $this->app->stickyGET('employee_schedule');
	// 	$date = $this->app->stickyGET('date_schedule');
	// 	if(!$date || $date=='') $date = $this->app->today;

	// 	$this->employee_field->js('change',$this->js()->reload(['employee_schedule'=>$this->employee_field->js()->val(),'date_schedule'=>$this->date_field->js()->val()]));
	// 	$this->date_field->js('change',$this->js()->reload(['employee_schedule'=>$this->employee_field->js()->val(),'date_schedule'=>$this->date_field->js()->val()]));
		
	// 	if(!$emp_id || !$date){
	// 		$this->add('View')->set('Please select employee and date to update');
	// 		return;
	// 	}

	// 	$employee = $this->add('xepan\hr\Model_Employee')->load($emp_id);

	// 	$model_task = $this->add('xepan\projects\Model_Task');
	// 	$model_task->addExpression('on_date')->set('DATE(starting_date)');
	// 	$model_task['type'] = 'Followup';
	// 	$model_task->addCondition('assign_to_id',$emp_id);
	// 	$model_task->addCondition('on_date',date('Y-m-d',strtotime($date)));

	// 	$g=$this->add('Grid');
	// 	$g->add('View',null,'grid_buttons')->set('Existing Schedules');
	// 	$g->setModel($model_task,['starting_date','task_name','assign_to']);

	// }

}