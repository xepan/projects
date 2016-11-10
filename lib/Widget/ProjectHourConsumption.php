<?php

namespace xepan\projects;

class Widget_ProjectHourConsumption extends \View{
	function init(){
		parent::init();
	}

	function recursiveRender(){
		$this->add('xepan\base\View_Chart')
     		->setType('bar')
     		->setModel($this->model,'name',['Estimate','Alloted','Consumed'])
     		->setGroup(['Estimate','Alloted','Consumed'])
     		->setTitle('Project Hour Consumption')
     		->addClass('col-md-8')
     		->rotateAxis();
		parent::recursiveRender();
	}
}