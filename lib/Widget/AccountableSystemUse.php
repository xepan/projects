<?php

namespace xepan\projects;

class Widget_AccountableSystemUse extends \View{
	function init(){
		parent::init();
	}

	function recursiveRender(){				
		$this->add('xepan\base\View_Chart')
     		->setType('bar')
     		->setModel($this->model,'name',['pending_works','please_receive','received_so_far','total_tasks_assigned','take_report_on_pending','check_submitted'])
     		->setGroup([['received_so_far','total_tasks_assigned'],['pending_works','take_report_on_pending']])
     		// ->setGroup(['self_pending','given_tasks_pending'])
     		->setTitle('Staff Accountable System Use')
     		->addClass('col-md-12')
     		// ->rotateAxis()
     		;
		parent::recursiveRender();
	}
}