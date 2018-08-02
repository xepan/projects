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
		$form = $this->add('Form');
		$form->add('xepan\base\Controller_FLC')
			->makePanelsCoppalsible(true)
			->layout([
				'date_range'=>'Filter~c1~3',
				'employee'=>'c2~3',
				'department'=>'c3~3',
				'FormButtons~&nbsp;'=>'c4~3'
			]);
			
		$date = $form->addField('DateRangePicker','date_range');
		$set_date = $this->app->today." to ".$this->app->today;
		if($from_date){
			$set_date = $from_date." to ".$to_date;
			$date->set($set_date);	
		}
		$emp_field = $form->addField('xepan\base\Basic','employee');
		$emp_field->setModel('xepan\projects\Model_EmployeeCommunicationActivity');
		$dept_field = $form->addField('DropDown','department')->setEmptyText('Please Select Department');
		$dept_field->setModel('xepan\hr\Model_Department');
		$form->addSubmit('Get Details');
		// if($from_date)
			// throw new \Exception($from_date, 1);
					

		$employee_comm = $this->add('xepan\projects\Model_EmployeeCommunicationActivity',['from_date'=>$from_date?:$this->app->today,'to_date'=>$to_date?:$this->api->nextDate($this->app->today)]);

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
		$grid->add("misc\Export");

		$assign_to_pending_task = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$assign_to_pending_task_m = $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_pending_task']);
			$assign_to_pending_task_m->addCondition('assign_to_id',$employee_id)
						->addCondition('assign_to_id','<>',null)
						->addCondition('status','Pending')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
					;		
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($assign_to_pending_task_m,['task_name','created_by','assign_to_','description','starting_date','deadline','estimate_time','status','priority','received_at','comment_count']);
			$grid->addPaginator(50);
			$grid->addHook('formatRow',function($g){
				$g->current_row_html['description'] = $g->model['description'];
			});
		});

		$grid->addMethod('format_assign_to_pending_task',function($g,$f)use($assign_to_pending_task){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Assigned To Me Pending Task',$g->api->url($assign_to_pending_task->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('assign_to_pending_task','assign_to_pending_task');

		$assign_to_inprogress_task = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$assign_to_inprogress_task_m = $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_inprocess_task']);
			$assign_to_inprogress_task_m->addCondition('assign_to_id',$employee_id)
						->addCondition('assign_to_id','<>',null)
						->addCondition('status','Inprogress')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
					;		
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($assign_to_inprogress_task_m,['task_name','created_by','assign_to_','description','starting_date','deadline','estimate_time','status','priority','received_at','comment_count']);
			$grid->addPaginator(50);
			$grid->addHook('formatRow',function($g){
				$g->current_row_html['description'] = $g->model['description'];
			});
		});

		$grid->addMethod('format_assign_to_inprogress_task',function($g,$f)use($assign_to_inprogress_task){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Assigned To InProgress Task',$g->api->url($assign_to_inprogress_task->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('assign_to_inprogress_task','assign_to_inprogress_task');

		$assign_to_complete_task = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$assign_to_complete_task_m = $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_comp_task']);
			$assign_to_complete_task_m->addCondition('assign_to_id',$employee_id)
						->addCondition('assign_to_id','<>',null)
						->addCondition('status','Completed')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
					;		
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($assign_to_complete_task_m,['task_name','created_by','assign_to_','description','starting_date','deadline','estimate_time','status','priority','received_at','comment_count']);
			$grid->addPaginator(50);
			$grid->addHook('formatRow',function($g){
				$g->current_row_html['description'] = $g->model['description'];
			});
		});

		$grid->addMethod('format_assign_to_complete_task',function($g,$f)use($assign_to_complete_task){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Assigned To Completed Task',$g->api->url($assign_to_complete_task->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('assign_to_complete_task','assign_to_complete_task');


	/*Assigned By Task*/

	$assign_by_pending_task = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$assign_by_pending_task_m = $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign__by_prending_task']);
			$assign_by_pending_task_m->addCondition('created_by_id',$employee_id)
						->addCondition('created_by_id','<>',null)
						->addCondition('status','Pending')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
					;		
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($assign_by_pending_task_m,['task_name','created_by','assign_to_','description','starting_date','deadline','estimate_time','status','priority','received_at','comment_count']);
			$grid->addPaginator(50);
			$grid->addHook('formatRow',function($g){
				$g->current_row_html['description'] = $g->model['description'];
			});
		});

		$grid->addMethod('format_assign_by_pending_task',function($g,$f)use($assign_by_pending_task){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Assigned By Pending Task',$g->api->url($assign_by_pending_task->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('assign_by_pending_task','assign_by_pending_task');

		$assign_by_inprogress_task = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$assign_by_inprogress_task_m = $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign__by_prending_task']);
			$assign_by_inprogress_task_m->addCondition('created_by_id',$employee_id)
						->addCondition('created_by_id','<>',null)
						->addCondition('status','Inprogress')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
					;		
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($assign_by_inprogress_task_m,['task_name','created_by','assign_to_','description','starting_date','deadline','estimate_time','status','priority','received_at','comment_count']);
			$grid->addPaginator(50);
			$grid->addHook('formatRow',function($g){
				$g->current_row_html['description'] = $g->model['description'];
			});
		});

		$grid->addMethod('format_assign_by_inprogress_task',function($g,$f)use($assign_by_inprogress_task){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Assigned By InProgress Task',$g->api->url($assign_by_inprogress_task->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('assign_by_inprogress_task','assign_by_inprogress_task');


		$assign_by_complete_task = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$assign_by_complete_task_m = $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign__by_complete_task']);
			$assign_by_complete_task_m->addCondition('created_by_id',$employee_id)
						->addCondition('created_by_id','<>',null)
						->addCondition('status','Completed')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
					;		
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($assign_by_complete_task_m,['task_name','created_by','assign_to_','description','starting_date','deadline','estimate_time','status','priority','received_at','comment_count']);
			$grid->addPaginator(50);
			$grid->addHook('formatRow',function($g){
				$g->current_row_html['description'] = $g->model['description'];
			});
		});

		$grid->addMethod('format_assign_by_complete_task',function($g,$f)use($assign_by_complete_task){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Assigned By Completed Task',$g->api->url($assign_by_complete_task->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('assign_by_complete_task','assign_by_complete_task');
	/*Over Due Task*/
		$overdue_task= $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$task =  $this->add('xepan\projects\Model_Task',['table_alias'=>'employee_assign_to_assigntask']);
			$task->addCondition('status',['Pending','Inprogress','Assigned'])
		    	 	->addCondition($task->dsql()->orExpr()
		    		->where('assign_to_id',$employee_id)
		    		->where($task->dsql()->andExpr()
					->where('created_by_id',$employee_id)
					->where('assign_to_id',null)));
			$task->addCondition('deadline','<',$this->app->now);			
			$task->addCondition('status','<>','Completed');
			$task->addCondition('created_at','>=',$_GET['from_date']);
			$task->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
					;		
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($task,['task_name','created_by','assign_to_','description','starting_date','deadline','estimate_time','status','priority','received_at','comment_count']);
			$grid->addPaginator(50);
			$grid->addHook('formatRow',function($g){
				$g->current_row_html['description'] = $g->model['description'];
			});
		});

		$grid->addMethod('format_overdue_task',function($g,$f)use($overdue_task){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Assigned By Completed Task',$g->api->url($overdue_task->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('overdue_task','overdue_task');

		/*total Send Message*/
		$send_msg= $this->add('VirtualPage')->set(function($page){

			$employee_id = $this->app->stickyGET('employee_id');
			$msg = $page->add('xepan\communication\Model_Communication_MessageSent')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
						->addCondition('created_by_id',$employee_id)
						// ->count();
					;		
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($msg,['from','to','title','description','status']);
			$grid->addPaginator(50);
			$grid->addHook('formatRow',function($g){
				$g->current_row_html['description'] = $g->model['description'];
			});
		});

		$grid->addMethod('format_total_send_message',function($g,$f)use($send_msg){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Assigned By Completed Task',$g->api->url($send_msg->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('total_send_message','total_send_message');

		/*total Send Email`s*/
		$send_email= $this->add('VirtualPage')->set(function($page){

			$employee_id = $this->app->stickyGET('employee_id');
			$send_emails = $this->add('xepan\communication\Model_Communication_Email_Sent')
						->addCondition('created_by_id',$employee_id)
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
					;		
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($send_emails,['from','to','title','description','status']);
			$grid->addPaginator(50);
			$grid->addHook('formatRow',function($g){
				$g->current_row_html['description'] = $g->model['description'];
			});
		});

		$grid->addMethod('format_total_send_emails',function($g,$f)use($send_email){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Assigned By Completed Task',$g->api->url($send_email->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('total_send_emails','total_send_emails');

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