<?php

namespace xepan\projects;

class Widget_DepartmentAccountableSystemUse extends \xepan\base\Widget{
	function init(){
		parent::init();
	    
          $this->report->enableFilterEntity('date_range');
          $this->report->enableFilterEntity('Department');

          $this->chart = $this->add('xepan\base\View_Chart');
     }

	function recursiveRender(){
		
		if(isset($this->report->department))
			$dept_id  = $this->report->department;  
		else
			$dept_id  = $this->app->employee['department_id'];

		$accountablity = $this->add('xepan\projects\Model_Widget_AccountableSystemUse',['dept_id'=>$dept_id,'entity'=>'Department','start_date'=>$this->report->start_date,'end_date'=>$this->app->nextDate($this->report->end_date)]);

		$this->chart->setType('bar')
     		        ->setModel($accountablity,'name',['pending_works','please_receive','received_so_far','total_tasks_assigned','take_report_on_pending','check_submitted'])
     		        ->setGroup([['received_so_far','total_tasks_assigned'],['pending_works','take_report_on_pending']])
     		        ->setTitle('Staff Accountable System Use')
     		        ->openOnClick('xepan_projects_widget_accountablesystemuse');

		return parent::recursiveRender();
	}
}