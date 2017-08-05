<?php

namespace xepan\projects;

/**
* 
*/
class page_report_employee extends \xepan\base\Page{

	public $title = "Employee Communication Report";
	function init(){
		parent::init();

		$form = $this->add('Form',null,null,['form/empty']);
		$date = $form->addField('DateRangePicker','date_range');	
		$emp_field = $form->addField('xepan\base\Basic','employee');
		$emp_field->setModel('xepan\projects\Model_EmployeeCommunication');
		$dept_field = $form->addField('DropDown','department')->setEmptyText('Please Select Department');
		$dept_field->setModel('xepan\hr\Model_Department');
		$form->addSubmit('Get Details');
			

		$employee_comm = $this->add('xepan\projects\Model_EmployeeCommunication',['from_date'=>$_GET['from_date'],'to_date'=>$_GET['to_date']]);
		
		if($_GET['employee_id']){
			$employee_comm->addCondition('id',$_GET['employee_id']);
		}
		if($_GET['department_id']){
			$employee_comm->addCondition('department_id',$_GET['department_id']);
		}

		$grid = $this->add('xepan\hr\Grid',null,null,['view/report/employee-comm-report']);
		$grid->setModel($employee_comm);

		if($form->isSubmitted()){
			
			$grid->js()->reload(
							[
								'employee_id'=>$form['employee'],
								'department_id'=>$form['department'],
								'from_date'=>$date->getStartDate()?:0,
								'to_date'=>$date->getEndDate()?:0
							]
						)->execute();
		}
	}
}