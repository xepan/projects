<?php

namespace xepan\projects;

class View_Detail extends \View{
	public $task_id;
	public $project_id = null;
	public $task_type;

	function init(){
		parent::init();		
		$p = $this;
		$task_id = $this->task_id;
		$project_id = $this->project_id;
		
		$model_task = $p->add('xepan\projects\Model_Formatted_Task')->tryLoad($task_id);
		$model_task->getElement('assign_to_id')->getModel()->addCondition('status','Active');

		if($this->project_id)
			$model_task->addCondition('project_id',$project_id);

		$detail_view = $p->add('xepan\projects\View_TaskDetail');

		if($model_task->ICanEdit()){
			if($model_task['status'] == 'Pending' && $model_task['assign_to_id'] != $this->app->employee->id)			
				goto elsepart;

			$temp = [];
			$temp = explode(',', $model_task['notify_to']);

			$temp1 = [];
			$temp1 = explode(',', $model_task['remind_via']);

			$task_form = $detail_view->add('Form',null,'task_form');
			$task_form->setLayout('view\task_form');
			$task_form->template->tryDel('assign_to');

			if($this->task_type != 'followup'){
				$task_form->layout->template->tryDel('display_wrapper');
			}

			$task_form->setModel($model_task,['assign_to_id','reminder_time','task_name','is_regular_work','describe_on_end','description','starting_date','deadline','priority','estimate_time','set_reminder','remind_via','remind_unit','notify_to','is_recurring','recurring_span','snooze_duration']);
			
			$task_form->getElement('notify_to')->set($temp)->js(true)->trigger('changed');
			$task_form->getElement('remind_via')->set($temp1)->js(true)->trigger('changed');
			
			if(!$task_id){
				$task_form->getElement('deadline')->js(true)->val('');
				$task_form->getElement('starting_date')->js(true)->val('');
				$task_form->getElement('reminder_time')->js(true)->val('');
			}

			$task_form->getElement('remind_via')
						->addClass('multiselect-full-width')
						->setAttr(['multiple'=>'multiple']);

			$task_form->getElement('notify_to')
						->addClass('multiselect-full-width')
						->setAttr(['multiple'=>'multiple']);			

			$snooze_reminder_field = $task_form->addField('checkbox','snooze_reminder','Enable Snoozing [Repetitive Reminder]');
			$task_form->addSubmit('Save')->addClass('btn btn-primary btn-block');

			$reminder_field = $task_form->getElement('set_reminder');
			$recurring_field = $task_form->getElement('is_recurring');
			
			$reminder_field->js(true)->univ()->bindConditionalShow([
				true=>['remind_via','notify_to','reminder_time','snooze_reminder']
			],'div.atk-form-row');

			$snooze_reminder_field->js(true)->univ()->bindConditionalShow([
				true=>['snooze_duration','remind_unit']
			],'div.atk-form-row');
		
			$recurring_field->js(true)->univ()->bindConditionalShow([
				true=>['recurring_span']
			],'div.atk-form-row');

				
			if($task_form->isSubmitted()){				
				
				if($task_form['is_regular_work'] ){
					if($task_form['assign_to_id'] != $this->app->employee->id && !$this->app->auth->model->isSuperUser())
						$task_form->displayError('assign_to_id','Regular Works cannot be assigned to others, unless you are super user');
					if($task_form['set_reminder']){
						$task_form->displayError('set_reminder','Regular Works cannot be set to remined');
					}

					if($task_form['is_recurring']){
						$task_form->displayError('is_recurring','Regular Works cannot recurring');
					}

					if($task_form['project_id']){
						$task_form->displayError('project_id','Regular Works cannot belong to any project');
					}

					if($task_form['deadline']){
						$task_form['deadline']=null;
					}
				}


				if(!$task_form['snooze_reminder']){
					$task_form['snooze_duration'] == null;
				}

				if($task_form['set_reminder'] && $task_form['reminder_time'] == null){
					$task_form->displayError('reminder_time','reminder_time field is required');
				}
				if($task_form['set_reminder'] && $task_form['snooze_duration'] && $task_form['remind_unit'] == null){
					$task_form->displayError('remind_unit','remind_unit field is required');
				}
				
				if($task_form['is_recurring'] && $task_form['recurring_span'] == null){
					$task_form->displayError('recurring_span','recurring_span field is required');
				}


				$task_form->save();
				
				// ONLY IN CASE OF 'ADDING NEW TASK' FROM DETAIL VIEW
				$m = $task_form->getModel();	
				if($m->loaded() AND $m['type'] == ''){
					$m['type'] = 'Task';
					$m->save();
				}

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
			elsepart:
			$model_task['assign_to_id'] = ' ';			
			$desc = $model_task['description'];
			$model_task['description'] = "";
			$detail_view_form = $detail_view->add('View',null,'task_form',['view\task_form']);
			
			if($model_task['set_reminder']){
				$model_task['set_reminder']='Yes';	
			}

			if($model_task['is_recurring']){
				$model_task['is_recurring']='Yes';	
			}

			$notify_array = explode(',', $model_task['notify_to']);
			$notified_employees = $this->add('xepan\hr\Model_Employee')->addCondition('id',$notify_array)->getRows(['name']);
			$notified_employees_names = [];
			foreach ($notified_employees as $temp_emp) {
				$notified_employees_names [] = $temp_emp['name'];
			}
			
			$model_task['notify_to'] = implode(", ", $notified_employees_names);

			$detail_view_form->setModel($model_task);
			$detail_view_form->template->setHtml('description1',$desc);
			$detail_view_form->template->tryDel('display_wrapper');
		}

		if($model_task->loaded()){																								
			$model_attachment = $this->add('xepan\projects\Model_Task_Attachment');
			$model_attachment->addCondition('task_id',$task_id);	
			$model_attachment->acl = 'xepan\projects\Model_Task';
			
			$attachment_acl_add = true;
			if($model_task['status'] == 'Completed')
				$attachment_acl_add = false;

			$attachment_crud = $detail_view->add('xepan\hr\CRUD',['allow_add'=>$attachment_acl_add],'attachment',['view\attachment-grid']);
			$attachment_crud->setModel($model_attachment,['file_id','thumb_url','filename'],['file_id','thumb_url','created_at','filename'])->addCondition('task_id',$task_id);
			$detail_view->template->trySet('attachment_count',$model_task['attachment_count']);

			$model_comment = $this->add('xepan\projects\Model_Comment');
			$model_comment->acl = 'xepan\projects\Model_Task';
			$model_comment->addCondition('task_id',$model_task->id);

			$comment_acl_add = true;
			if($model_task['status'] == 'Completed')
				$comment_acl_add = false;

			$comment_grid = $detail_view->add('xepan\hr\CRUD',['allow_add'=>$comment_acl_add],'commentgrid',['view\comment-grid']);
			$model_comment->addHook('beforeSave',[$this,'onAction']);
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

			if($task_m['created_by_id'] === $this->app->employee->id){
				$task_m['creator_unseen_comment_count'] = '0';
			}elseif($task_m['assign_to_id'] === $this->app->employee->id){
				$task_m['assignee_unseen_comment_count'] = '0';
			}
			$task_m->save();

		});
	}	

	function onAction($m){		
		$task = $this->add('xepan\projects\Model_Task');
		$task->tryLoadBy('id',$m['task_id']);
		$m['action'] = $task['status'];
	}	
}