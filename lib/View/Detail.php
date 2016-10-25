<?php

namespace xepan\projects;

class View_Detail extends \View{
	public $task_id;
	public $project_id = null;

	function init(){
		parent::init();

		$p = $this;
		$task_id = $this->task_id;
		$project_id = $this->project_id;
																		
		$model_task = $p->add('xepan\projects\Model_Formatted_Task')->tryLoad($task_id);

		if($this->project_id)
			$model_task->addCondition('project_id',$project_id);

		$detail_view = $p->add('xepan\projects\View_TaskDetail');


		if($model_task->ICanEdit()){			
			$task_form = $detail_view->add('Form',null,'task_form');
			$task_form->setLayout('view\task_form');
			$task_form->template->tryDel('assign_to');

			$task_form->setModel($model_task,['assign_to_id','task_name','description','starting_date','deadline','priority','estimate_time','set_reminder','remind_via','remind_value','remind_unit','notify_to','is_recurring','recurring_span','reminder_time_compare_with']);
			$task_form->getElement('deadline')->js(true)->val('');
			$task_form->getElement('remind_via')
						->addClass('multiselect-full-width')
						->setAttr(['multiple'=>'multiple']);

			$task_form->getElement('notify_to')
						->addClass('multiselect-full-width')
						->setAttr(['multiple'=>'multiple']);			

			$task_form->addSubmit('Save')->addClass('btn btn-primary btn-block');

			if($task_form->isSubmitted()){				
				if($task_form['set_reminder'] && $task_form['remind_value'] == null){
					$task_form->displayError('remind_value','This field is required');
				}
				if($task_form['set_reminder'] && $task_form['remind_unit'] == null){
					$task_form->displayError('remind_unit','This field is required');
				}
				
				if($task_form['is_recurring'] && $task_form['recurring_span'] == null){
					$task_form->displayError('recurring_span','This field is required');
				}

				$task_form->save();
				$task_form->getModel()->unload();
				
				$js=[
					$task_form->js()->univ()->successMessage('saved'),
					$task_form->js()->univ()->closeDialog(),
					$this->js()->_selector('.xepan-tasklist-grid')->trigger('reload')
					];
				$p->js(null,$js)->execute();
			}
		}
		

		else{
			$model_task['assign_to_id'] = ' ';			
			$desc = $model_task['description'];
			$model_task['description'] = "";
			$detail_view_form = $detail_view->add('View',null,'task_form',['view\task_form']);
			$detail_view_form->setModel($model_task);
			$detail_view_form->template->setHtml('description1',$desc);
		}

		if($model_task->loaded()){																								
			$model_attachment = $this->add('xepan\projects\Model_Task_Attachment');
			$model_attachment->addCondition('task_id',$task_id);	
			$model_attachment->acl = 'xepan\projects\Model_Task';
			
			$attachment_acl_add = true;
			if($model_task['status'] == 'Completed')
				$attachment_acl_add = false;

			$attachment_crud = $detail_view->add('xepan\hr\CRUD',['allow_add'=>$attachment_acl_add],'attachment',['view\attachment-grid']);
			$attachment_crud->setModel($model_attachment,['file_id','thumb_url'])->addCondition('task_id',$task_id);
			$detail_view->template->trySet('attachment_count',$model_task['attachment_count']);

			$model_comment = $this->add('xepan\projects\Model_Comment');
			$model_comment->acl = 'xepan\projects\Model_Task';
			$model_comment->addCondition('task_id',$model_task->id);

			$comment_acl_add = true;
			if($model_task['status'] == 'Completed')
				$comment_acl_add = false;

			$comment_grid = $detail_view->add('xepan\hr\CRUD',['allow_add'=>$comment_acl_add],'commentgrid',['view\comment-grid']);
			$comment_grid->setModel($model_comment,['comment'],['is_seen_by_creator','is_seen_by_assignee','comment','on_action','employee','created_at']);

			$detail_view->template->trySet('comment_count',$model_task['comment_count']);
			
			$comment_grid->grid->addHook('formatRow',function($g)use($model_task){				
				if($model_task['status']=='Completed'){
					$g->current_row_html['edit'] = ' ';
				    $g->current_row_html['delete'] = ' ';
				}

				if(($model_task['created_by_id'] == $this->app->employee->id) && $g->model['is_seen_by_assignee'] == true){
					$g->current_row_html['edit'] = ' ';
				    $g->current_row_html['delete'] = ' ';
				}

				if(($model_task['assign_to_id'] == $this->app->employee->id) && $g->model['is_seen_by_creator'] == true){
					$g->current_row_html['edit'] = ' ';
				    $g->current_row_html['delete'] = ' ';
				}
			});

			$attachment_crud->grid->addHook('formatRow',function($g)use($model_task){				
				$g->current_row_html['edit'] = ' ';
				
				if($model_task['status']=='Completed'){
				    $g->current_row_html['delete'] = ' ';
				}
			});
		}
		
		$this->on('shown.bs.tab','a[href=#tab-comment]',function($js,$data)use($model_task){							
			$task_m = $this->add('xepan\projects\Model_Task');
			$task_m->addCondition('id',$model_task->id);
			$task_m->tryLoadAny();
			
			if(!$task_m->loaded())
				return;

			$comment_m = $this->add('xepan\projects\Model_Comment');
			$comment_m->addCondition('task_id',$task_m->id);
					
			if($task_m['created_by_id'] == $this->app->employee->id){
				foreach ($comment_m as $c) {
					$c['is_seen_by_creator'] = true;
					$c->save();
				}
			}

			if($task_m['assign_to_id'] == $this->app->employee->id){				
				foreach ($comment_m as $c) {
					$c['is_seen_by_assignee'] = true;
					$c->save();
				}
			}

		});
	}		
}