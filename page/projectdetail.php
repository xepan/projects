<?php

namespace xepan\projects;

class page_projectdetail extends \xepan\projects\page_sidemenu{
	public $title = "Project Detail";
	function init(){
		parent::init();

		$project_id = $this->app->stickyGET('project_id');
		$task_id = $this->app->stickyGET('task_id');

		$model_project = $this->add('xepan\projects\Model_Project');

		$top_view = $this->add('xepan\projects\View_TopView',null,'topview');
		$top_view->setModel($model_project)->load($project_id);
		
		$task_list_view = $this->add('xepan\hr\CRUD',null,'leftview',['view\tasklist']);	
		// $task_list_view = $this->add('xepan\projects\View_TaskList',null,'leftview');
		$task_list_view->setModel('xepan\projects\Task');

		$task_detail_view = $this->add('xepan\projects\View_Task',null,'rightview');
		$task_detail_view_url = $this->api->url(null,['cut_object'=>$task_detail_view->name]);
		$task=$this->add('xepan\projects\Model_Task');
		if($task_id){
			$task->addCondition('id',$task_id);
			$task->tryLoadAny();
		}
		
		$f=$task_detail_view->add('Form',null,'form');
		$f->setModel($task);

		if($f->isSubmitted()){
			$f->save();
			$f->js()->univ()->successMessage('saved')->execute();
		}


		/***************************************************************************
			Js to show task detail view
		***************************************************************************/

		$task_list_view->on('click','.name',function($js,$data)use($task_detail_view_url,$task_detail_view){
			$js_new=[
				$this->js()->_selector('#left_view')->removeClass('col-md-12'),
				$this->js()->_selector('#left_view')->addClass('col-md-7'),
				$this->js()->_selector('#right_view')->show(),
				$task_detail_view->js()->reload(['task_id'=>$data['id']?:''],null,$task_detail_view_url)

			];

			return $js_new;
		});

		/***************************************************************************
			Js to revert changes on cross icon click on task detail view
		***************************************************************************/
		$js_new=[
			$this->js()->_selector('#right_view')->hide(),
			$this->js()->_selector('#left_view')->removeClass('col-md-7'),
			$this->js()->_selector('#left_view')->addClass('col-md-12')
		];
		$task_detail_view->js('click',$js_new)->_selector('.glyphicon.glyphicon-remove');
	}

	function defaultTemplate(){
		return['page\projectdetail'];
	}
}