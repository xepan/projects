<?php

namespace xepan\projects;

class Model_FollowUp extends Model_Task
{	

	public $status=['Pending','Submitted','Completed','Assigned','Inprogress'];
	public $force_delete = false;
	public $actions=[
		'Pending'=>['mark_complete','stop_recurrence','reset_deadline','stop_reminder'],
		'Inprogress'=>['mark_complete','stop_recurrence','stop_reminder'],
		'Assigned'=>['receive','reject','stop_recurrence','reset_deadline','stop_reminder'],
		'Submitted'=>['mark_complete','reopen','stop_recurrence','stop_reminder'],
		'Completed'=>['stop_recurrence']
	];

	function init(){
		parent::init();

		$this->addCondition('type','Followup');
	}

}
