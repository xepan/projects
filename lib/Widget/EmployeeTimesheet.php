<?php

namespace xepan\projects;

class Widget_EmployeeTimesheet extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('project');
		$this->report->enableFilterEntity('employee');

		$this->grid = $this->add('Grid');
	}

	function recursiveRender(){
		$timesheet = $this->add('xepan\projects\Model_Timesheet');

		if(isset($this->report->project))
			$timesheet->addCondition('project_id',$this->report->project);
		if(isset($this->report->start_date))
			$timesheet->addCondition('starttime','>',$this->report->start_date);
		if(isset($this->report->end_date))
			$timesheet->addCondition('endtime','<',$this->app->nextDate($this->report->end_date));
		if(isset($this->report->employee))
			$timesheet->addCondition('employee_id',$this->report->employee);

		$this->grid->setModel($timesheet,['task','starttime','endtime','duration','project']);
		$this->grid->addPaginator(10);

		return parent::recursiveRender();
	}
}