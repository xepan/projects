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

			$task_form->setModel($model_task,['assign_to_id','task_name','description','starting_date','deadline','priority','estimate_time','set_reminder','remind_via','remind_value','remind_unit','notify_to','is_recurring','recurring_span']);
			$task_form->getElement('remind_via')
						->addClass('multiselect-full-width')
						->setAttr(['multiple'=>'multiple']);

			$task_form->getElement('notify_to')
						->addClass('multiselect-full-width')
						->setAttr(['multiple'=>'multiple']);			

			$task_form->addSubmit('Save')->addClass('btn btn-primary btn-block');

			if($task_form->isSubmitted()){				
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
			$detail_view->add('View',null,'task_form',['view\task_form'])->setModel($model_task);
		}

		if($model_task->loaded()){																								
			$model_attachment = $this->add('xepan\projects\Model_Task_Attachment');
			$model_attachment->addCondition('task_id',$task_id);	
				
			$attachment_crud = $detail_view->add('xepan\hr\CRUD',null,'attachment',['view\attachment-grid']);
			$attachment_crud->setModel($model_attachment,['file_id','thumb_url'])->addCondition('task_id',$task_id);
			$detail_view->template->trySet('attachment_count',$model_task['attachment_count']);
			

			$model_comment = $this->add('xepan\projects\Model_Comment');
			$model_comment->addCondition('task_id',$model_task->id);
			$model_comment->addCondition('employee_id',$this->app->employee->id);

			$comment_grid = $detail_view->add('xepan\hr\CRUD',null,'commentgrid',['view\comment-grid']);
			$comment_grid->setModel($model_comment,['comment','employee','on_action']);
			$detail_view->template->trySet('comment_count',$model_task['comment_count']);
		}
	}		
}