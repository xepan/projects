<?php


namespace xepan\projects;


class View_TaskCalendar extends \View{

	public $employee_field_to_set=null;
	public $startingdate_field_to_set=null;

	public $event_array=[];
	public $calview;
	public $form;

	public $defaultView='agendaWeek';
	public $title_field='assign_to';

	function init(){
		parent::init();

		$this->js(true)->_load('bootstrap-datetimepicker')
        ->_css('libs/bootstrap-datetimepicker')
        ;
        $this->js(true)->_load('select2.min')->_css('libs/select2');
		$this->form = $this->add('View');
		$this->calview = $this->add('View');
	}

	function setModel($model){
		if(! $model instanceof \xepan\projects\Model_Task){
			throw $this->exception('Only Model Task or its extended models should be provided');
		}
		$this->event_array=[];
		// {title:value.title,start:value.start,document_id:value.document_id,'client_event_id':value._id}
		foreach ($model as $m) {
			$e =['title'=>$m[$this->title_field],'start'=>$m['starting_date'],'task_id'=>$m->id,'allDay'=>false,'end'=>$m['starting_date'],'desc'=>$m->description()];
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
		$this->js(true)->_load('moment.min')->_load('fullcalendar.min')->_load('xepan-followup-scheduler12');
		$this->js(true)->univ()->showFollowupCalendar($this->calview,$this->event_array, $this->employee_list, $this->employee_field_to_set, $this->startingdate_field_to_set,$this->form, $this->defaultView);
		return parent::render();
	}

}