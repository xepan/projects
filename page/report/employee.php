<?php

namespace xepan\projects;

/**
* 
*/
class page_report_employee extends \xepan\base\Page{

	public $title = "Employee Communication Report";
	function init(){
		parent::init();
		$from_date = $this->app->stickyGET('from_date');
		$to_date = $this->app->stickyGET('to_date');
		$form = $this->add('Form',null,null,['form/empty']);
		$date = $form->addField('DateRangePicker','date_range');
		$set_date = $this->app->today." to ".$this->app->today;
		if($from_date){
			$set_date = $from_date." to ".$to_date;
			$date->set($set_date);	
		}
		$emp_field = $form->addField('xepan\base\Basic','employee');
		$emp_field->setModel('xepan\projects\Model_EmployeeCommunication');
		$dept_field = $form->addField('DropDown','department')->setEmptyText('Please Select Department');
		$dept_field->setModel('xepan\hr\Model_Department');
		$form->addSubmit('Get Details');
		// if($from_date)
			// throw new \Exception($from_date, 1);
					

		$employee_comm = $this->add('xepan\projects\Model_EmployeeCommunication',['from_date'=>$from_date,'to_date'=>$to_date]);
		if($_GET['from_date']){
			$employee_comm->from_date = $_GET['from_date'];
		}		
		if($_GET['employee_id']){
			$employee_comm->addCondition('id',$_GET['employee_id']);
		}
		if($_GET['department_id']){
			$employee_comm->addCondition('department_id',$_GET['department_id']);
		}

		$grid = $this->add('xepan\hr\Grid',null,null,['view/report/employee-comm-report']);
		$grid->setModel($employee_comm);
		$grid->addPaginator(50);

		if($form->isSubmitted()){
			
			$form->js()->univ()->redirect($this->app->url(),[
								'employee_id'=>$form['employee'],
								'department_id'=>$form['department'],
								'from_date'=>$date->getStartDate()?:0,
								'to_date'=>$date->getEndDate()?:0
							]
							
						)->execute();
		}
	}
}