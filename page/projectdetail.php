<?php

namespace xepan\projects;

class page_projectdetail extends \xepan\projects\page_sidemenu{
	public $title = "Project Detail";
	public $breadcrumb=['Home'=>'index','Project'=>'xepan_projects_project','Detail'=>'#'];

	function init(){
		parent::init();

		$this->js(true)->_load('timer.jquery');

		$project_id = $this->app->stickyGET('project_id');
		if(!$project_id) return;
		
		$task_id = $this->app->stickyGET('task_id');
		$search = $this->app->stickyGET('search');

		$employee_id = $this->recall('employee',$this->app->employee->id);
		$status_searched = $this->recall('status_searched','Pending');
		$search_string = $this->recall('search_string',false);
		$created_by = $this->recall('createdby',false);
		$priority = $this->recall('priority',false);

		$model_project = $this->add('xepan\projects\Model_Formatted_Project')->load($project_id);
		
		/***************************************************************************
			Adding views
		***************************************************************************/
		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project);

		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('project_id',$project_id);
		$employee = $this->add('xepan\hr\Model_Employee');
		/***************************************************************************
			FILTER FORM
		***************************************************************************/
	    // $option_form = $this->add('Form',null,'leftview');
	    // $option_form->setLayout('view\option_form');
	    // $option_form->addField('dropdown','status','')
	    // 	->setValueList(['Pending'=>'Pending','Submitted'=>'Submitted','Completed'=>'Completed'])
	    // 	->setEmptyText('All');

	    // $option_form->addField('dropdown','createdby','')
	    // 	->setValueList(['1'=>'Created By','2'=>'Assigned To','3'=>'Created By And Assigned To','4'=>'Created By Or Assigned To'])
	    // 	->setEmptyText('Select An Option');

	    // $option_form->addField('dropdown','priority','')
	    // 	->setValueList(['25'=>'Low','50'=>'Medium','75'=>'High','90'=>'Critical'])
	    // 	->setEmptyText('All');	
	    		
	    // $option_form->addField('Line','search_string','Search')->set($search_string);
	    // $emp_name = $option_form->addField('dropdown','employee')->setEmptyText('All');
	    // $emp_name->setModel($employee);
	    // $emp_name->set($employee_id);
	    // $option_form->addSubmit('Apply Filters')->addClass('btn btn-primary');

	    

	// if($employee_id){
	//     if($created_by){
	//     	if($created_by == '1'){
	//     		$my_task->addCondition('created_by_id',$employee_id);
	//     	}else if($created_by == '2'){
	//     		$my_task->addCondition('assign_to_id',$employee_id);
	//     	}else if($created_by == '3'){
	//     		$my_task->addCondition(
	// 					$my_task->dsql()->andExpr()
	// 					->where('created_by_id',$employee_id)
	// 					->where('assign_to_id',$employee_id)
	// 				);
	//     	}else if($created_by == '4'){
	//     		$my_task->addCondition(
	// 					$my_task->dsql()->orExpr()
	// 					->where('created_by_id',$employee_id)
	// 					->where('assign_to_id',$employee_id)
	// 				);
	//     	}
	//     }
	// }

	    // if($status_searched)	    	
	    // 	$my_task->addCondition('status',$status_searched);

	    // if($priority){
	    // 	$my_task->addCondition('priority',$priority);
	    // }

		// if($search_string){	

		// 	$my_task->addExpression('Relevance')->set('MATCH(task_name, description) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		// 	$my_task->addCondition('Relevance','>',0);
	 // 		$my_task->setOrder('Relevance','Desc');
		// }

	    $task_assigned_to_me = $this->add('xepan\projects\View_TaskList',null,'leftview');	    
	    $task_assigned_by_me = $this->add('xepan\projects\View_TaskList',null,'middleview');	    
	    $task_waiting_for_approval = $this->add('xepan\projects\View_TaskList',null,'rightview');	    

	    // if($option_form->isSubmitted()){	

	    // 	$this->memorize('status_searched',$option_form['status']);
		   //  $this->memorize('employee',$option_form['employee']);
		   //  $this->memorize('search_string',$option_form['search_string']);
		   //  $this->memorize('createdby',$option_form['createdby']);
		   //  $this->memorize('priority',$option_form['priority']);

    	// 	$task_assigned_to_me->js()->reload()->execute();
	    // }
		$status = 'Completed';

	    $task_assigned_to_me_model = $this->add('xepan\projects\Model_Formatted_Task');
	    $task_assigned_to_me_model
	    			->addCondition(
	    				$task_assigned_to_me_model->dsql()->orExpr()
	    					->where('assign_to_id',$this->app->employee->id)
	    					->where(
    								$task_assigned_to_me_model->dsql()->andExpr()
    									->where('created_by_id',$this->app->employee->id)
    									->where('assign_to_id',null)
	    							)
	    				);

	    $task_assigned_by_me_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('project_id',$project_id)
										  ->addCondition('created_by_id',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',null)
										  ->addCondition('status','<>','Submitted');

	    $task_waiting_for_approval_model = $this->add('xepan\projects\Model_Formatted_Task')
										  ->addCondition('project_id',$project_id)
										  ->addCondition('created_by_id',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',$this->app->employee->id)
										  ->addCondition('assign_to_id','<>',null)
										  ->addCondition('status','Submitted');	
		
		$task_assigned_to_me->setModel($task_assigned_to_me_model);
		$task_assigned_by_me->setModel($task_assigned_by_me_model);
		$task_waiting_for_approval->setModel($task_waiting_for_approval_model);

		if($task_id){
			$task->load($task_id);			
		}
		$task_assigned_to_me_url = $this->api->url(null,['cut_object'=>$task_assigned_to_me->name]);

		// $task_view_url = $task_assigned_to_me->getUrl();

		/***************************************************************************
			Virtual page for TASK DETAIL
		***************************************************************************/
		$self = $this;
		$self_url = $this->app->url(null,['cut_object'=>$this->name]);

		$vp = $this->add('VirtualPage');
		$vp->set(function($p)use($self,$self_url,$task_assigned_to_me,$task_assigned_to_me_url){

			$task_id = $this->app->stickyGET('task_id')?:0;
			$project_id = $this->app->stickyGET('project_id');

			$p->add('xepan\projects\View_Detail',['task_id'=>$task_id,'project_id'=>$project_id]);
		});	

		/***************************************************************************
			Js to show task detail view etc.
		***************************************************************************/
		$task_assigned_to_me->js('click')->_selector('.task-item')->univ()->frameURL('TASK DETAIL',[$this->api->url($vp->getURL()),'task_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);

		$top_view->js('click',$this->js()->univ()->frameURL("ADD NEW TASK",$this->api->url($vp->getURL())))->_selector('.add-task');
		
		$task_assigned_to_me->js('click',$task_assigned_to_me->js()->reload(['delete_task_id'=>$this->js()->_selectorThis()->data('id')]))->_selector('.do-delete');

		if($_GET['delete_task_id']){
			$delete_task=$this->add('xepan\projects\Model_Task');
			$delete_task->load($_GET['delete_task_id']);
			$delete_task->delete();
			$task_assigned_to_me->js(true,$this->js()->univ()->successMessage('Task Deleted'))->_load('jquery.nestable')->nestable(['group'=>1]);
		}


	/***************************************************************************
	  Timesheet PLAY/STOP
	***************************************************************************/
	$task_assigned_to_me->on('click','.current_task_btn',function($js,$data)use($task_assigned_to_me){
			
			$model_close_timesheet = $this->add('xepan\projects\Model_Timesheet');

			$model_close_timesheet->addCondition('employee_id',$this->app->employee->id);
			$model_close_timesheet->setOrder('starttime','desc');
			$model_close_timesheet->tryLoadAny();

			if($model_close_timesheet->loaded()){
				if(!$model_close_timesheet['endtime']){
					$model_close_timesheet['endtime'] = $this->app->now;
					$model_close_timesheet->save();
				}
			}

			if($data['action']=='start'){

				$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
					
				$model_timesheet['task_id'] = $data['id'];
				$model_timesheet['employee_id'] = $this->app->employee->id;
				$model_timesheet['starttime'] = $this->app->now;
				$model_timesheet->save();

				return [
						$this->js()->_selector('.current_task_btn')->removeClass('fa-stop')->addClass('fa-play'),
						$this->js()->_selector('.dd3-content')->removeClass('alert alert-info'),
						$js->removeClass('fa-play')->addClass('fa-stop')->data('action','stop'),
						$this->js()->_selector('.dd3-content[data-id='.$data['id'].']')->addClass('alert alert-info'),
					];
			}

			return $js->removeClass('fa-stop')->addClass('fa-play')->data('action','start');	
		});

	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}
}