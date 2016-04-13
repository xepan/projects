<?php

namespace xepan\projects;

class page_projectdetail extends \xepan\projects\page_sidemenu{
	public $title = "Project Detail";
	public $breadcrumb=['Home'=>'index','Project'=>'xepan_projects_project','Detail'=>'#'];

	function init(){
		parent::init();
		$project_id = $this->app->stickyGET('project_id');
		$task_id = $this->app->stickyGET('task_id');

		$model_project = $this->add('xepan\projects\Model_Formatted_Project')->load($project_id);

		/***************************************************************************
			Adding views
		***************************************************************************/
		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project);

		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('project_id',$project_id);

		/***************************************************************************
			FILTER FORM
		***************************************************************************/
	    $option_form = $this->add('Form',null,'leftview');
	    $option_form->setLayout('view\option_form');
	    $option_form->addField('dropdown','filter','')->setValueList(['All'=>'All','Completed'=>'Completed','Pending'=>'Pending']);
	    $option_form->addField('checkbox','mytask','');
	    $option_form->addSubmit('Update');
	    

	    $task_list_m = $this->add('xepan\projects\Model_Formatted_Task')
						->addCondition('project_id',$project_id);

	    $filter = $this->api->stickyGET('filter');
	    $mytask = $this->api->stickyGET('mytask');

	    if($mytask == 'true'){
	    	$task_list_m->addCondition('employee_id',$this->app->employee->id);
	    }


	    if($filter == 'Completed'){
	    	$task_list_m->addCondition('status','Completed');	
	    }else if($filter == 'Pending'){
	    	$task_list_m->addCondition('status','Pending');
	    }

	    $running_task_id = $this->add('xepan\projects\Model_Employee')
	    					->load($this->app->employee->id)
	    					->get('running_task_id');

	    $task_list_view = $this->add('xepan\projects\View_TaskList',null,'leftview');	    

	    if($option_form->isSubmitted()){	

    		$task_list_view->js()->reload(['filter'=>$option_form['filter']?:'', 'mytask'=>$option_form['mytask']])->execute();
	    }
		
	    
		$task_list_view->setModel($task_list_m);
		$task_list_view->add('xepan\hr\Controller_ACL',['action_btn_group'=>'xs']);

		$task_list_view->add('xepan\base\Controller_Avatar',['options'=>['size'=>20,'border'=>['width'=>0]],'name_field'=>'employee','default_value'=>'']);

		// task detail view for showing/editing details of tasks.
		$task_detail_view = $this->add('xepan\projects\View_TaskDetail',['task_list_view'=>$task_list_view],'rightview');
		$task_detail_view_url = $this->api->url(null,['cut_object'=>$task_detail_view->name]);

		// if there is already some task added, only then apply these conditions.
		if($task_id){
			// $task->addCondition('id',$task_id);
			$task->load($task_id);			
		}

		$task_detail_view->setModel($task);

		/***************************************************************************
			Js to show task detail view
		***************************************************************************/

		$task_list_view->on('click','.task-item',function($js,$data)use($task_detail_view_url,$task_detail_view){
			$js_new = [
				$this->js()->_selector('#left_view')->removeClass('col-md-12'),
				$this->js()->_selector('#left_view')->addClass('col-md-7'),
				$this->js()->_selector('#right_view')->show(),
				$task_detail_view->js()->reload(['task_id'=>$data['id']?:''],null,$task_detail_view_url)
			];
			return $js_new;
		});

		$top_view->on('click','.add-task',function($js,$data)use($task_detail_view_url,$task_detail_view){
			$js_new = [
				$task_detail_view->js()->reload(null,null,$task_detail_view_url),
				$this->js()->_selector('#left_view')->removeClass('col-md-12'),
				$this->js()->_selector('#left_view')->addClass('col-md-7'),
				$this->js()->_selector('#right_view')->show()
			];
			return $js_new;
		});

		
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

		$task_list_view->js(true)->_load('jquery.nestable')->nestable(['group'=>1]);

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