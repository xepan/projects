<?php


namespace xepan\projects;


class View_TaskCalendar extends \View{

	public $add_employee_filter=true;
	public $add_task_types_filter=true;
	public $default_task_type=false;
	public $add_task_sub_types_filter=true;

	public $employee_field_to_set=null;
	public $startingdate_field_to_set=null;
	public $follow_type_field_to_set=null;

	public $task_sub_types=[];
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

		$m = $this->add('xepan\projects\Model_Config_TaskSubtype')->tryLoadAny();
		$this->task_sub_types = explode(",", $m['value']);

		$this->vp = $this->add('VirtualPage');
		$this->vp->set([$this,'openTask']);

	}

	function setModel($model){
		if(! $model instanceof \xepan\projects\Model_Task){
			throw $this->exception('Only Model Task or its extended models should be provided');
		}
		$this->event_array=[];
		// {title:value.title,start:value.start,document_id:value.document_id,'client_event_id':value._id}
		foreach ($model as $m) {
			$color = ($m['status'] !='Completed' && (($m['type']=='Followup' && strtotime($m['starting_date']) < strtotime($this->app->today)) || ($m['type']=='Task' && strtotime($m['deadline']) < strtotime($this->app->today)) ) ) ? 'red':null;
			$color = (strtolower($m['status']) =='completed')? 'green':$color;
			$e =['title'=>$m[$this->title_field],'start'=>$m['starting_date'],'task_id'=>$m->id,'allDay'=>false,'end'=>$m['starting_date'],'desc'=>$m->description(),'assign_to_id'=>$m['assign_to_id'],'type'=>$m['type'],'sub_type'=>$m['sub_type'],'color'=>$color, 'icon'=>($m['type']=='Task'?'tasks':($m['type']=='Followup'?'refresh':'bell-o'))];
			$this->event_array[] = $e;
		}
	}

	function recursiveRender(){
		$e_all = $this->add('xepan\hr\Model_Employee')->addCondition('status','Active')->getRows();
		$this->employee_list = array_combine(array_column($e_all, 'id'),array_column($e_all, 'name'));
		return parent::recursiveRender();
	}

	function render(){
		$this->js(true)->_css('fullcalendar-3.9.0/fullcalendar');//->_css('compiled/calendar');
		$this->js(true)->_load('fullcalendar-3.9.0/lib/moment.min')->_load('fullcalendar-3.9.0/fullcalendar.min')->_load('xepan-followup-scheduler13');
		// showFollowupCalendar: function(obj,events_passed,defaultView, employee_list, add_employee_filter, add_task_types_filter,default_task_type, add_task_sub_types_filter, task_sub_types, employee_field_to_set, startingdate_field_to_set,form){
		$this->js(true)->univ()->showFollowupCalendar($this->calview,$this->event_array, $this->defaultView, $this->employee_list, $this->add_employee_filter, $this->add_task_types_filter, $this->default_task_type, $this->add_task_sub_types_filter, $this->task_sub_types, $this->employee_field_to_set, $this->startingdate_field_to_set,$this->form,$this->vp->getURL(),$this->follow_type_field_to_set);
		return parent::render();
	}


	function openTask($p){
		$task_id = $p->app->stickyGET('task_id');
		// $task_view = $p->add('xepan\projects\View_Detail',['task_id'=>$task_id,'task_type'=>'followup']);	
		$my_followups = $p->add('xepan\hr\CRUD',['allow_add'=>false,'entity_name'=>'Followup','grid_class'=>'xepan\projects\View_TaskList']);
		$my_followups_model = $p->add('xepan\projects\Model_Task');
		$my_followups_model->addCondition('id',$task_id);
		$my_followups->setModel($my_followups_model);
		$my_followups->add('xepan\base\Controller_Avatar',['name_field'=>'assign_to','image_field'=>'assign_to_image','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$my_followups_model]);
	}

}