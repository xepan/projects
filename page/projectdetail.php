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
	    $option_form = $this->add('Form',null,'leftview');
	    $option_form->setLayout('view\option_form');
	    $option_form->addField('dropdown','status','')
	    	->setValueList(['Pending'=>'Pending','Submitted'=>'Submitted','Completed'=>'Completed'])
	    	->setEmptyText('All');

	    $option_form->addField('dropdown','createdby','')
	    	->setValueList(['1'=>'Created By','2'=>'Assigned To','3'=>'Created By And Assigned To','4'=>'Created By Or Assigned To'])
	    	->setEmptyText('Select An Option');

	    $option_form->addField('dropdown','priority','')
	    	->setValueList(['25'=>'Low','50'=>'Medium','75'=>'High','90'=>'Critical'])
	    	->setEmptyText('All');	
	    		
	    $option_form->addField('Line','search_string','Search')->set($search_string);
	    $emp_name = $option_form->addField('dropdown','employee')->setEmptyText('All');
	    $emp_name->setModel($employee);
	    $emp_name->set($employee_id);
	    $option_form->addSubmit('Apply Filters')->addClass('btn btn-primary');

	    $task_list_m = $this->add('xepan\projects\Model_Formatted_Task')
						->addCondition('project_id',$project_id);
	    

	if($employee_id){
	    if($created_by){
	    	if($created_by == '1'){
	    		$task_list_m->addCondition('created_by_id',$employee_id);
	    	}else if($created_by == '2'){
	    		$task_list_m->addCondition('employee_id',$employee_id);
	    	}else if($created_by == '3'){
	    		$task_list_m->addCondition(
						$task_list_m->dsql()->andExpr()
						->where('created_by_id',$employee_id)
						->where('employee_id',$employee_id)
					);
	    	}else if($created_by == '4'){
	    		$task_list_m->addCondition(
						$task_list_m->dsql()->orExpr()
						->where('created_by_id',$employee_id)
						->where('employee_id',$employee_id)
					);
	    	}
	    }
	}

	    if($status_searched)	    	
	    	$task_list_m->addCondition('status',$status_searched);

	    if($priority){
	    	$task_list_m->addCondition('priority',$priority);
	    }

		if($search_string){	

			$task_list_m->addExpression('Relevance')->set('MATCH(task_name, description) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
			$task_list_m->addCondition('Relevance','>',0);
	 		$task_list_m->setOrder('Relevance','Desc');
		}

	    $task_list_view = $this->add('xepan\projects\View_TaskList',null,'leftview');	    

	    if($option_form->isSubmitted()){	

	    	$this->memorize('status_searched',$option_form['status']);
		    $this->memorize('employee',$option_form['employee']);
		    $this->memorize('search_string',$option_form['search_string']);
		    $this->memorize('createdby',$option_form['createdby']);
		    $this->memorize('priority',$option_form['priority']);

    		$task_list_view->js()->reload()->execute();
	    }
		
		$task_list_view->setModel($task_list_m);
		$task_list_view->add('xepan\hr\Controller_ACL',['action_btn_group'=>'xs']);

		$task_list_view->add('xepan\base\Controller_Avatar',['options'=>['size'=>20,'border'=>['width'=>0]],'name_field'=>'employee','default_value'=>'']);

		if($task_id){
			$task->load($task_id);			
		}

		$task_list_view_url = $this->api->url(null,['cut_object'=>$task_list_view->name]);

		// $task_view_url = $task_list_view->getUrl();

		/***************************************************************************
			Virtual page for TASK DETAIL
		***************************************************************************/
		$self = $this;
		$self_url = $this->app->url(null,['cut_object'=>$this->name]);

		$vp = $this->add('VirtualPage');
		$vp->set(function($p)use($self,$self_url,$task_list_view,$task_list_view_url){

			$task_id = $this->app->stickyGET('task_id')?:0;
			$project_id = $this->app->stickyGET('project_id');
			
			$model_task = $this->add('xepan\projects\Model_Task')->tryLoad($task_id);
			$model_task->addCondition('project_id',$project_id);

			$detail_view = $p->add('xepan\projects\View_TaskDetail');

			$task_form = $detail_view->add('Form',null,'task_form');
			$task_form->setLayout('view\task_form');

			$task_form->setModel($model_task,['employee_id','task_name','description','starting_date','deadline','priority','estimate_time','set_reminder','remind_via','remind_value','remind_unit','notify_to','is_recurring','recurring_span']);
			$task_form->getElement('remind_via')
						->addClass('multiselect-full-width')
						->setAttr(['multiple'=>'multiple']);

			$task_form->getElement('notify_to')
						->addClass('multiselect-full-width')
						->setAttr(['multiple'=>'multiple']);			


			if($task_form->isSubmitted()){

				$task_form->save();
				$js=[
					$task_form->js()->univ()->successMessage('saved'),
					$task_form->js()->univ()->closeDialog(),
					$task_list_view->js()->reload(null,null,$task_list_view_url)
					];
				$p->js(null,$js)->execute();
			}

			if($model_task->loaded()){								
				$model_attachment = $this->add('xepan\projects\Model_Task_Attachment');
				$model_attachment->addCondition('task_id',$task_id);	
					
				$attachment_crud = $detail_view->add('xepan\hr\CRUD',null,'attachment',['view\attachment-grid']);
				$attachment_crud->setModel($model_attachment,['file_id','thumb_url'])->addCondition('task_id',$task_id);

				$attachment_count = $model_attachment->count()->getOne();
				$detail_view->template->trySet('attachment_count',$attachment_count);
				
				$model_comment = $this->add('xepan\projects\Model_Comment');
				$model_comment->addCondition('task_id',$model_task->id);
				$model_comment->addCondition('employee_id',$this->app->employee->id);

				$comment_grid = $detail_view->add('xepan\hr\CRUD',null,'commentgrid',['view\comment-grid']);
				$comment_grid->setModel($model_comment,['comment','employee']);
				
				$comment_count = $model_comment->count()->getOne();
				$detail_view->template->trySet('comment_count',$comment_count);
			}
		});	

		/***************************************************************************
			Js to show task detail view etc.
		***************************************************************************/
		$task_list_view->on('click','.task-item',function($js,$data)use($vp){
				return $js->univ()->dialogURL("TASK DETAIL",$this->api->url($vp->getURL(),['task_id'=>$data['id']]));
			});


		$top_view->js('click',$this->js()->univ()->dialogURL("ADD NEW TASK",$this->api->url($vp->getURL())))->_selector('.add-task');
		
		$task_list_view->on('click','.do-delete',function($js,$data){
			$delete_task=$this->add('xepan\projects\Model_Task');
			$delete_task->load($data['id']);
			$delete_task->delete();
			$js_new=[
					$js->closest('li')->hide(),
					$this->js()->univ()->successMessage('Delete SuccessFullly')
			];
			return $js_new;

		});

		// $task_list_view->js(true)->_load('jquery.nestable')->nestable(['group'=>1]);

	/***************************************************************************
	  Timesheet PLAY/STOP
	***************************************************************************/
	$task_list_view->on('click','.current_task_btn',function($js,$data)use($task_list_view){
			
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