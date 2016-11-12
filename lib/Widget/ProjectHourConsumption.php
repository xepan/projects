<?php

namespace xepan\projects;

class Widget_ProjectHourConsumption extends \xepan\base\Widget{
	function init(){
		parent::init();
		
		$this->report->enableFilterEntity('date_range');

		$this->chart = $this->add('xepan\base\View_Chart');
	}

	function recursiveRender(){
		$hour_consumption = $this->add('xepan\projects\Model_Widget_HourConsumption',['start_date'=>$this->report->start_date,'end_date'=>$this->app->nextDate($this->report->end_date)]);	
 		 		
 		$this->chart->setType('bar')
 			 ->setModel($hour_consumption,'name',['Estimate','Alloted','Consumed'])
 		     ->setGroup(['Estimate','Alloted','Consumed'])
 		     ->setTitle('Project Hour Consumption')
 		     ->rotateAxis();

		return parent::recursiveRender();
	}
}