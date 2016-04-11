<?php

namespace xepan\projects;

class View_TaskDetail extends \View{
	
	public $task_list_view=null;

	function init(){
		parent::init();
		$self = $this;
		$self_url=$this->app->url(null,['cut_object'=>$this->name]);


		/***************************************************************************
			Virtual page for assigning task.
		***************************************************************************/	
		$vp = $this->add('VirtualPage');
		$vp->set(function($p)use($self,$self_url){
						
			$model_task = $p->add('xepan\projects\Model_Task')->load($_GET['task_id']);
			// $this->app->currentEmployee()->ref()->queryfield()->count
			// add spot and place view on that spot with that count
			$form = $p->add('Form');
			$form->setLayout('view\assign_form');
			$form->addField('radio','assign_subtasks')->setValueList(['All SubTasks','Leave Reassigned SubTasks']);
			$form->setModel($model_task,['employee_id']);
			
			if($form->isSubmitted()){
				
				if($form['assign_subtasks']==0){
					
					foreach($model_task->ref('SubTasks') as $st){
						$st['employee_id'] = $form['employee_id'];
						$st->saveAndUnload();
					}
				}

				if($form['assign_subtasks']==1){
					foreach($model_task->ref('SubTasks')->addCondition('employee_id',$model_task['employee_id']) as $st){
						$st['employee_id'] = $form['employee_id'];
						$st->saveAndUnload();
					}
				}

				foreach($model_task->ref('SubTasks')->addCondition('employee_id',null) as $st){
					$st['employee_id'] = $form['employee_id'];
					$st->saveAndUnload();
				}

				$form->save();
				$form->js('null',$self->js()->reload(null,null,$self_url))->univ()->closeDialog()->execute();
			}

		});

		/***************************************************************************
			Virtual page for assigning followers
		***************************************************************************/
		$vp2 = $this->add('VirtualPage');
		$vp2->set(function($p){

			$model_task = $p->add('xepan\projects\Model_Task');
			$model_task->load($p->app->stickyGET('task_id'));

			$model_employee = $p->add('xepan\hr\Model_Employee');
			$model_follower_task_association = $p->add('xepan\projects\Model_Follower_Task_Association');
			
			$form = $p->add('Form');
			$follower_field = $form->addField('line','name')->set(json_encode($model_task->getAssociatedFollowers()));

			// Selectable for "task can have many followers" 

			$follower_grid = $p->add('xepan\base\Grid');

			$follower_grid->setModel($model_employee,['name']);
			$follower_grid->addSelectable($follower_field);

			if($form->isSubmitted()){

				$model_task->removeAssociateFollowers();
				
				$selected_followers = array();
			 	$selected_followers = json_decode($form['name'],true);

				foreach ($selected_followers as $followers) {
					$model_follower_task_association->addCondition('task_id',$_GET['task_id']);
					$model_follower_task_association['employee_id'] = $followers;
					$model_follower_task_association->saveAndUnload();
				}

				$form->js()->univ()->closeDialog()->execute(); 
			}
		});
		

		/***************************************************************************
			js click function for assign task 
		***************************************************************************/
		$this->on('click','#assigntask',function($js,$data)use($vp){
			return $js->univ()->dialogURL("ASSIGN TASK TO EMPLOYEE",$this->api->url($vp->getURL(),['task_id'=>$data['task_id']]));
		});

		/***************************************************************************
			js click function for adding followers.
		***************************************************************************/
		$this->on('click','#addfollowers',function($js,$data)use($vp2){
			return $js->univ()->dialogURL("ADD PEOPLE TO FOLLOW THIS TASK",$this->api->url($vp2->getURL(),['task_id'=>$data['task_id']]));
		});
	}

	function setModel($model,$fields=null){		
		$m = parent::setModel($model,$fields);
		$this->add('xepan\base\Controller_Avatar',['options'=>['size'=>30],'name_field'=>'employee','default_value'=>'??']);

		$task = $this->model;
		$task_detail_view = $this;
		$task_detail_view_url = $this->api->url(null,['cut_object'=>$task_detail_view->name]);
		

		$p_v = $task_detail_view->add('HtmlElement',null,'parent_name')
				->setElement('a')
				->setAttr('href','#')
				->set($task['parent']);	
		$p_v->js('click',$task_detail_view->js()->reload(['task_id'=>$task['parent_id']]));
		

		/***************************************************************************
			Adding SubTasks.
		***************************************************************************/
		if($task['parent_id']==null){
			
			$subtask = $task_detail_view->add('Button',null,'subtask')->set('Add SubTasks');
			$subtask->setAttr('data-id',$task->id);
					
			$subtask->on('click',null,function($js,$data)use($task_detail_view_url,$task_detail_view){
				$js_new = [
					$task_detail_view->js()->reload(['parent_id'=>$data['id']],null,$this->api->url($task_detail_view_url,['task_id'=>'']))
				];
				return $js_new;
			});
		}

		/***************************************************************************
			Tab Showing SubTasks/Comments.
		***************************************************************************/
		
		if($task->loaded()){
			$subtask = $task->ref('SubTasks');			
			$grid = $subtask_grid = $task_detail_view->add('xepan\hr\Grid',null,'showsubtask',['view\subtasks']);
			$subtask_grid->setModel($subtask);
			$subtask_grid->add('xepan\base\Controller_Avatar',['options'=>['size'=>30],'name_field'=>'employee','default_value'=>'??']);
			$grid->addQuickSearch(['task_name']);
		}
		
		/***************************************************************************
			Form to add tasks.
		***************************************************************************/	
		
		$f = $task_detail_view->add('Form',null,'form');
		$f->setLayout(['view\task_form']);
		$f->setModel($task,['task_name','description','starting_date','deadline']);
		$f->addSubmit('Save');

		if($f->isSubmitted()){
			$parent_task = $this->add('xepan\projects\Model_Task')->tryLoad($_GET['parent_id']?:0);
			$f->model['employee_id'] = $parent_task['employee_id'];
			$f->save();
			$js=[$f->js()->univ()->successMessage('saved')];
			$js[] = $task_detail_view->js()->reload(['task_id'=>$f->model->id,'parent_id'=>$task['parent_id']]);
			$js[] = $this->task_list_view->js()->reload();
			$this->js(null,$js)->execute();
		}

		/***************************************************************************
			Form to add comments on task.
		***************************************************************************/
		$comment_f = $task_detail_view->add('Form',null,'commentform');
		$comment_f->addField('text','comment');	
		$comment_f->addSubmit('ADD');

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
		$comment_grid = $task_detail_view->add('xepan\hr\Grid',null,'commentgrid',['view\comment-grid'])->setModel('xepan\projects\Comment',['comment','name'])->addCondition('task_id',$task->id);

		// $comment_grid->addQuickSearch(['name']);
		/***************************************************************************
			Js to revert changes on cross icon click on task detail view
		***************************************************************************/
		$js_new = [
			$this->owner->js()->_selector('#right_view')->hide(),
			$this->owner->js()->_selector('#left_view')->removeClass('col-md-7'),
			$this->owner->js()->_selector('#left_view')->addClass('col-md-12')
		];
		$task_detail_view->js('click',$js_new)->_selector('.glyphicon.glyphicon-remove');

		return $m;
	}

	function defaultTemplate(){
		return['view\taskdetail'];
	}
}