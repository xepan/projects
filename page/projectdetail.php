<?php

namespace xepan\projects;

class page_projectdetail extends \xepan\projects\page_sidemenu{
	public $title = "Project Detail";
	function init(){
		parent::init();
		$project_id = $this->app->stickyGET('project_id');
		$task_id = $this->app->stickyGET('task_id');
		$parent_id = $this->app->stickyGET('parent_id');

		$model_project = $this->add('xepan\projects\Model_Project');

		/***************************************************************************
			Adding views
		***************************************************************************/
		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project)->load($project_id);


		
		// crud added for edit, delete, action purpose.
	$task_list_view = $this->add('xepan\projects\View_TaskList',null,'leftview');	
		$task_list_view->setModel('xepan\projects\Task')
			->addCondition('parent_id',null)
			->addCondition('project_id',$project_id);
		$task_list_view->add('xepan\hr\Controller_ACL');

		$task_list_view->add('xepan\base\Controller_Avatar',['options'=>['size'=>20],'name_field'=>'employee','default_value'=>'']);

		// task detail view for showing/editing details of tasks.
		$task_detail_view = $this->add('xepan\projects\View_TaskDetail',['task_list_view'=>$task_list_view],'rightview');
		$task_detail_view_url = $this->api->url(null,['cut_object'=>$task_detail_view->name]);

		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('project_id',$project_id);

		if($parent_id){
			$task->addCondition('parent_id',$parent_id);
		}

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
				$task_detail_view->js()->reload(['task_id'=>$data['id']?:'','parent_id'=>''],null,$task_detail_view_url)
			];
			return $js_new;
		});

		$top_view->on('click','.add-task',function($js,$data){
			// return $js->univ()->alert('hello');
			$js_new = [
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

	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}
}