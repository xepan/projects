<?php

namespace xepan\projects;

class page_projectandtaskreport extends page_reportsidebar{
	public $title = "Project And Task Report";
	function init(){
		parent::init();

		/*******************************************************************
		 GETTING VALUES FROM URL	
		********************************************************************/
		$from_date = $this->app->stickyGET('from_date')?:$this->app->today;
		$to_date = $this->app->stickyGET('to_date')?:$this->app->nextDate($this->app->today);
		
		/*******************************************************************
		 EMPLOYEE MODEL AND EXPRESSIONS
		********************************************************************/
		$timesheet = $this->add('xepan\projects\Model_Timesheet');
		
		/*******************************************************************
	 	 FORM TO ENTER INFORMATION
		********************************************************************/	
		$form = $this->add('Form');
		$form->addField('DatePicker','from_date')->set($this->app->today);
		$form->addField('DatePicker','to_date')->set($this->app->today);
		$form->addField('Dropdown','projects');
		$form->addField('Dropdown','tasks');
		$form->addSubmit('Get Report')->addclass('btn btn-primary btn-sm btn-block');	

		// GRID WILL BE ADDED ON THIS VIEW
		$view = $this->add('View');

		/*******************************************************************
		 ADDING GRID ON VIEW AND SETTING MODEL
		********************************************************************/
		$grid = $view->add('Grid');			
		$grid->setModel($timesheet,['employee','starttime','endtime','duration']);
		
		/*******************************************************************
		 HANDLING FORM SUBMISSION
		********************************************************************/
		if($form->isSubmitted()){
			$array = [
						'from_date'=>$form['from_date'],
						'to_date'=>$form['to_date'],
					 ];
			$view->js()->reload($array)->execute();
		}
		
	}
}