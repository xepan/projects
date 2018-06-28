<?php


namespace xepan\projects;


class View_TaskCalendar extends \View{

	public $employee_field_to_set=null;
	public $startingdate_field_to_set=null;

	public $event_array=[];

	function init(){
		parent::init();

	}

	function setModel($model){
		if(! $model instanceof \xepan\projects\Model_Task){
			throw $this->exception('Only Model Task or its extended models should be provided');
		}
		$this->event_array=[];
		// {title:value.title,start:value.start,document_id:value.document_id,'client_event_id':value._id}
		foreach ($model as $m) {
			$e =['title'=>$m['assign_to'],'start'=>$m['starting_date'],'document_id'=>$m->id,'allDay'=>false,'end'=>$m['starting_date']];
			$this->event_array[] = $e;
		}
	}

	function recursiveRender(){
		$e_all = $this->add('xepan\hr\Model_Employee')->addCondition('status','Active')->getRows();
		$this->employee_list = array_combine(array_column($e_all, 'id'),array_column($e_all, 'name'));
		return parent::recursiveRender();
	}

	function render(){
		$this->js(true)->_css('libs/fullcalendar')->_css('compiled/calendar');
		$this->js(true)->_load('moment.min')->_load('fullcalendar.min')->_load('xepan-followup-scheduler');
		$this->js(true)->univ()->showFollowupCalendar($this,$this->event_array, $this->employee_list, $this->employee_field_to_set, $this->startingdate_field_to_set);
		return parent::render();
	}

}