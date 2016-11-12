<?php

namespace xepan\projects;

class Widget_AccountableSystemUse extends \xepan\base\Widget{
	function init(){
		parent::init();
	    
          $this->report->enableFilterEntity('date_range');

          $this->chart = $this->add('xepan\base\View_Chart');
     }

	function recursiveRender(){				
		$this->chart->setType('bar')
     		       ->setModel($this->model,'name',['pending_works','please_receive','received_so_far','total_tasks_assigned','take_report_on_pending','check_submitted'])
     		       ->setGroup([['received_so_far','total_tasks_assigned'],['pending_works','take_report_on_pending']])
     		       ->setTitle('Staff Accountable System Use')
     		       ->addClass('col-md-12');
		parent::recursiveRender();
	}
}