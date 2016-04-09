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
		$task_list_view = $this->add('xepan\hr\CRUD',['allow_add'=>false],'leftview',['view\tasklist']);	
		$task_list_view->setModel('xepan\projects\Task')->addCondition('project_id',$project_id);

		// task detail view for showing/editing details of tasks.
		$task_detail_view = $this->add('xepan\projects\View_Task',null,'rightview');
		$task_detail_view_url = $this->api->url(null,['cut_object'=>$task_detail_view->name]);

		// $task_detail_view->add('View')->set("Task ID " . $task_id );
		// $task_detail_view->add('View')->set("Parent ID " . $parent_id );
		// $task_detail_view->add('View')->set("Action " . $action );
		
		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('project_id',$project_id);

		// if there is already some task added, only then apply these conditions.
		if($task_id){
			// $task->addCondition('id',$task_id);
			$task->load($task_id);
		}

		if($parent_id){
			
			$parent_task = $this->add('xepan\projects\Model_Task')->load($parent_id);
			
			$task->addCondition('parent_id',$parent_id)
				 ->addCondition('employee',$parent_task['employee']);
		}


		$task_detail_view->setModel($task);

		/***************************************************************************
			Adding SubTasks.
		***************************************************************************/
		

		$p_v = $task_detail_view->add('HtmlElement',null,'parent_name')
				->setElement('a')
				->setAttr('href','#')
				->set($task['parent']);	
		$p_v->js('click',$task_detail_view->js()->reload(['task_id'=>$task['parent_id']]));

		if($task['parent_id']==null){
			
			$subtask = $task_detail_view->add('Button',null,'subtask')->set('Add SubTasks');
			$subtask->setAttr('data-id',$task_id);
					
			$subtask->on('click',null,function($js,$data)use($task_detail_view_url,$task_detail_view){
				$js_new = [
					$task_detail_view->js()->reload(['parent_id'=>$data['id']],null,$this->api->url($task_detail_view_url,['task_id'=>'']))
				];
				return $js_new;
			});
		}

		/***************************************************************************
			Showing SubTasks.
		***************************************************************************/
		if($task->loaded()){
			$task->load($task_id);
			$subtask = $task->ref('SubTasks');
			
			$task_detail_view->add('xepan\hr\Grid',null,'showsubtask',['view\subtasks'])->setModel($subtask);
		}
		
		/***************************************************************************
			Form to add tasks.
		***************************************************************************/	
		$f = $task_detail_view->add('Form',null,'form');
		$f->setModel($task,['task_name','description','starting_date','deadline']);
		$f->addSubmit('Save');

		/***************************************************************************
			Form to add commnets on task.
		***************************************************************************/
		$comment_f = $task_detail_view->add('Form',null,'commentform');
		$comment_f->addField('text','comment');	
		$comment_f->addSubmit('ADD');

		/***************************************************************************
			Handling Form Submission
		***************************************************************************/
		if($f->isSubmitted()){
			$f->save();
			$js=[$f->js()->univ()->successMessage('saved')];
			$js[] = $task_detail_view->js()->reload(['task_id'=>$f->model->id,'parent_id'=>$task['parent_id']]);
			$js[] = $task_list_view->js()->reload();
			$this->js(null,$js)->execute();
		}

		if($comment_f->isSubmitted()){
			$model_comment = $this->add('xepan\projects\Model_Comment');

			$model_comment->addCondition('task_id',$_GET['task_id']);
			$model_comment->addCondition('name',$this->app->employee->id);

			$model_comment['comment'] = $comment_f['comment'];
			$model_comment->save();

			$f->js()->univ()->successMessage('comment Saved')->execute();
		}

		/***************************************************************************
			Grid to show comments
		***************************************************************************/
			$task_detail_view->add('xepan\hr\Grid',null,'commentgrid',['view\comment-grid'])->setModel('xepan\projects\Comment',['comment','name'])->addCondition('task_id',$task_id);
		/***************************************************************************
			Js to show task detail view
		***************************************************************************/

		$task_list_view->on('click','.name',function($js,$data)use($task_detail_view_url,$task_detail_view){
			$js_new = [
				$this->js()->_selector('#left_view')->removeClass('col-md-12'),
				$this->js()->_selector('#left_view')->addClass('col-md-7'),
				$this->js()->_selector('#right_view')->show(),
				$task_detail_view->js()->reload(['task_id'=>$data['id']?:'','parent_id'=>''],null,$task_detail_view_url)
			];
			return $js_new;
		});

		/***************************************************************************
			Js to revert changes on cross icon click on task detail view
		***************************************************************************/
		$js_new = [
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