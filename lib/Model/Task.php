<?php

namespace xepan\projects;

class Model_Task extends \xepan\base\Model_Table
{	
	public $table = "task";
	public $title_field ='task_name';
	public $acl = false;
	public $status=['Pending','Submitted','Completed','Assigned','Inprogress'];
	public $force_delete = false;
	public $actions=[
		'Pending'=>['submit','mark_complete','stop_recurrence','reset_deadline','stop_reminder'],
		'Inprogress'=>['submit','mark_complete','stop_recurrence','stop_reminder'],
		'Assigned'=>['receive','reject','stop_recurrence','reset_deadline','stop_reminder'],
		'Submitted'=>['mark_complete','reopen','stop_recurrence','stop_reminder'],
		'Completed'=>['stop_recurrence']
	];

	function init()
	{
		parent::init();

		$this->hasOne('xepan\projects\Project','project_id');
		$this->hasOne('xepan\hr\Employee','assign_to_id')->defaultValue($this->app->employee->id);
		$this->hasOne('xepan\hr\Employee','created_by_id')->defaultValue($this->app->employee->id);
		$this->hasOne('xepan\base\Contact','related_id')->display(array('form'=>'autocomplete\Basic'));
		
		$this->getElement('related_id')->getModel()->title_field = "name_with_type";

		$this->addField('task_name');
		$this->addField('description')->type('text')->display(['form'=>'xepan\base\RichText']);
		$this->addField('deadline')->display(['form'=>'DateTimePicker'])->type('datetime');
		$this->addField('starting_date')->display(['form'=>'DateTimePicker'])->type('datetime');
		$this->addField('estimate_time')/*->display(['form'=>'TimePicker'])*/;
		$this->addField('status')->defaultValue('Pending');
		$this->addField('type')->enum(['Task','Followup','Reminder']);

		$config = $this->add('xepan\projects\Model_Config_TaskSubtype');
		$config->tryLoadAny();

		$this->addField('sub_type')->enum(explode(",",$config['value']));

		$this->addField('priority')->setValueList(['25'=>'Low','50'=>'Medium','75'=>'High','90'=>'Critical'])->EmptyText('Priority')->defaultValue(50);
		$this->addField('set_reminder')->type('boolean');
		$this->addField('remind_via')->display(['form'=>'xepan\base\DropDown'])->setValueList(['Email'=>'Email','SMS'=>'SMS','Notification'=>'Notification']);
		$this->addField('remind_value')->type('number');
		$this->addField('remind_unit')->setValueList(['Minutes'=>'Minutes','hours'=>'Hours','day'=>'Days']);
		$this->addField('is_recurring')->type('boolean');
		$this->addField('recurring_span')->setValueList(['Daily'=>'Daily','Weekely'=>'Weekely','Fortnight'=>'Fortnight','Monthly'=>'Monthly','Quarterly'=>'Quarterly','Halferly'=>'Halferly','Yearly'=>'Yearly']);
		$this->addField('is_reminded')->type('boolean');
		$this->addField('is_reminder_only')->type('boolean')->defaultValue(false);
		$this->addField('reminder_time')->display(['form'=>'DateTimePicker'])->type('datetime');
		$this->addField('reminder_time_compare_with')->setValueList(['starting_date'=>'starting_date','deadline'=>'deadline'])->defaultValue('starting_date');
		$this->addField('snooze_duration');
		
		$this->addField('is_regular_work')->type('boolean')->defaultValue(false);
		$this->addField('describe_on_end')->type('boolean')->defaultValue(true);
		
		$this->addField('manage_points')->type('boolean')->defaultValue(false);
		$this->addField('applied_rules');
		
		$employee_model = $this->add('xepan\hr\Model_Employee')->addCondition('status','Active');		
		$this->addField('notify_to')->display(['form'=>'xepan\base\DropDown'])->setModel($employee_model);
		
		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
		$this->addField('updated_at')->type('datetime');
		$this->addField('rejected_at')->type('datetime');
		$this->addField('received_at')->type('datetime');
		$this->addField('submitted_at')->type('datetime');
		$this->addField('reopened_at')->type('datetime');
		$this->addField('completed_at')->type('datetime');
		$this->addField('last_comment_time')->type('datetime');
		$this->addField('comment_count')->type('int');
		$this->addField('creator_unseen_comment_count')->type('int');
		$this->addField('assignee_unseen_comment_count')->type('int');

		$this->hasMany('xepan\projects\Follower_Task_Association','task_id');
		$this->hasMany('xepan\projects\Comment','task_id');	
		$this->hasMany('xepan\projects\Timesheet','task_id');	
		$this->hasMany('xepan\projects\Task_Attachment','task_id');	

		$this->addHook('beforeSave',[$this,'beforeSave']);
		$this->addHook('beforeSave',[$this,'dirtyReminder']);
		$this->addHook('beforeSave',[$this,'nullifyFields']);
		$this->addHook('beforeSave',[$this,'notifyAssignement']);
		$this->addHook('beforeSave',[$this,'checkEmployeeHasEmail']);
		$this->addHook('beforeDelete',[$this,'closeTimesheet']);
		$this->addHook('beforeDelete',[$this,'checkExistingTimeSheet']);
		$this->addHook('beforeDelete',[$this,'checkExistingFollwerTaskAssociation']);
		$this->addHook('beforeDelete',[$this,'canUserDelete']);
		$this->addHook('beforeDelete',[$this,'checkExistingComment']);
		$this->addHook('beforeDelete',[$this,'checkExistingTaskAttachment']);

		$this->is([
			'task_name|required'
			]);

		$this->addExpression('follower_count')->set(function($m){
			return $m->refSQL('xepan\projects\Follower_Task_Association')->count();
		});

		$this->addExpression('task_complete_in_deadline')->set(function($m,$q){
			return $q->expr('if([0] >= [1],1,0)',[$m->getElement('deadline'),$m->getElement('completed_at')]);
		})->type('boolean');

		if($this->app->employee->id){
			$this->addExpression('created_by_me')->set(function($m,$q){
				return $q->expr("IF([0]=[1],1,0)",[
							$m->getElement('created_by_id'),
							$this->app->employee->id
						]
					);
			});


			$this->addExpression('total_comment')->set('comment_count');

			// $this->addExpression('total_comment_seen_by_creator')->set('creator_unseen_comment_count');
			// $this->addExpression('total_comment_seen_by_assignee')->set('assignee_unseen_comment_count');

			// $this->addExpression('creator_unseen_comment')->set('creator_unseen_comment_count');
			// $this->addExpression('assignee_unseen_comment')->set('assignee_unseen_comment_count');


			$this->addExpression('created_comment_color')->set(function($m,$q){
				return $q->expr("IF([0] > 0,'RED','GRAY')",[$m->getElement('creator_unseen_comment_count')]);
			});


			$this->addExpression('assignee_comment_color')->set(function($m,$q){
				return $q->expr("IF([0] > 0,'RED','GRAY')",[$m->getElement('assignee_unseen_comment_count')]);
			});

			$this->addExpression('comment_color')->set(function($m,$q){
				return $q->expr('IF([0],[1],[2])',
												[
													$m->getElement('created_by_me'),
													$m->getElement('created_comment_color'),
													$m->getElement('assignee_comment_color')
												]);
			});

			$this->addExpression('created_by_image')->set(function($m,$q){
				return $q->expr('[0]',[$m->refSQL('created_by_id')->fieldQuery('image')]);
			});
		}
		
		$this->addExpression('assigned_to_image')->set(function($m,$q){
			return $q->expr('[0]',[$m->refSQL('assign_to_id')->fieldQuery('image')]);
		});

		$this->addExpression('related_name')->set(function($m,$q){
			$contact = $this->add('xepan\base\Model_Contact');
			$contact->addCondition('id',$m->getElement('related_id'));
			$contact->setLimit(1);
			return $contact->fieldQuery('name');
		});

		$this->addExpression('priority_name')->set(function($m){
			return $m->dsql()->expr(
					"IF([0]=90,'Critical',
						if([0]=75,'High',
						if([0]=50,'Medium',
						if([0]=25,'Low','Low'	
						))))",

					  [
						$m->getElement('priority')
					  ]

					);
		});

		// $this->addExpression('last_comment_time')->set(function($m,$q){
		// 		return $this->add('xepan\projects\Model_Comment')
		// 					->addCondition('task_id',$m->getElement('id'))
		// 					->setOrder('created_at','desc')
		// 					->setLimit(1)
		// 					->fieldQuery('created_at');
		// });

		$this->addExpression('assign_employee_status')->set(function($m,$q){
			return $m->refSQL('assign_to_id')
							->fieldQuery('status');
		});

		$this->addExpression('created_by_employee_status')->set(function($m,$q){
			return $m->refSQL('created_by_id')
							->fieldQuery('status');
		});

		$this->addExpression('contact_name')->set(function($m,$q){
			return $this->add('xepan\base\Model_Contact')
						->addCondition('id',$m->getElement('related_id'))
						->setLimit(1)
						->fieldQuery('name');
		});

		$this->addExpression('contact_organization')->set(function($m,$q){
			return $this->add('xepan\base\Model_Contact')
						->addCondition('id',$m->getElement('related_id'))
						->setLimit(1)
						->fieldQuery('organization');
		});

 	}

 	function description($br='<br/>'){
 		$str  ='<div style="text-align:left">';

 		if($this['description'])
 			$str .= $this['description'];
 		$str .= 'On: ' . $this['created_at'] .$br;
 		$str .= 'By: ' . $this['created_by'] .$br;
 		$str .= 'To: ' . $this['assign_to'] .$br;
 		$str .= 'At: ' . $this['starting_date'] .$br;
 		$str .='</div>';
 		return $str;
 	}

 	function dirtyReminder(){
 		if($this['is_reminded'] AND ($this['reminder_time'] > $this->app->now)) 			
 			$this['is_reminded'] = null;
 	}
	
	function nullifyFields($m){
		if(!$m['set_reminder']){
			$m['remind_via'] = null;
			$m['notify_to'] = null;
			$m['reminder_time'] = null;
			$m['snooze_duration'] = null;
			$m['remind_unit'] = null;
		}

		if(!$m['is_recurring']){
			$m['recurring_span'] = null;
		}		
	}

 	function checkEmployeeHasEmail(){
 		if($this['set_reminder']){
 			$remind_via_array = [];
			$remind_via_array = explode(',', $this['remind_via']);

 			$employee_array = [];
			$employee_array = explode(',', $this['notify_to']);
				if(in_array("Email", $remind_via_array)){
					foreach ($employee_array as $value){
						if(!$value) continue; // in case user kept 'Please select' also
						$emp = $this->add('xepan\hr\Model_Employee')->load($value);
						if(!$emp['first_email'])
							throw $this->exception($emp['name'].' has no email defined','ValidityCheck')->setField('notify_to');
					}
 				}
 		}
 	}

	function beforeSave(){		
		if($this['task_name'] == '')
			throw $this->exception('Title field is required','ValidityCheck')->setField('task_name');
			
		if($this->isDirty('assign_to_id') && $this['assign_to_id'] != $this->app->employee->id){
			$this['status'] = 'Assigned';
		}

		if($this['type'] != 'Reminder' && $this->isDirty('assign_to_id') && !isset($this->isRejecting)){
			if($this->loaded() && !$this->ICanAssign())
				throw $this->exception('You are not authorised to assign this task','ValidityCheck')
							->setField('assign_to_id');
		}

		if($this['type'] != 'Reminder' && $this->isDirty('assign_to_id') && !$this->ICanAssign() && !isset($this->isRejecting)){
			// if($this->loaded() & !$this->ICanReject())
				throw $this->exception('You are not authorised to reject this task','ValidityCheck')
							->setField('assign_to_id');
		}		

		if(!$this['id'] && $this['assign_to_id'] && $this['assign_to_id'] != $this['created_by_id']){
			$this['status'] = "Assigned";
		}
		$this['updated_at'] = $this->app->now;
		if(!$this['deadline']) $this['deadline'] = $this['starting_date'];
		
		if($this['set_reminder']){									
			if($this['reminder_time'] == '')
				throw $this->exception('Remind At is required','ValidityCheck')->setField('reminder_time');

			if($this['remind_via'] == null || $this['notify_to'] == null)
				$this->app->js()->univ()->alert('Remind Via And Notify To Are Compulsory')->execute();			
		}

		if(strtotime($this['deadline']) < strtotime($this['starting_date'])){			
			throw $this->exception('Deadline can not be smaller then starting date','ValidityCheck')->setField('deadline');
		}
					
		if($this['starting_date'] == '' AND $this['type'] != 'Reminder')
			throw $this->exception('Starting Date is required','ValidityCheck')->setField('starting_date');
		
		if($this['type'] == 'Followup')
			if($this['related_id'] == '')
				throw $this->exception('Related Contact is required','ValidityCheck')->setField('related_id');
		
	
		
		if($this['is_recurring'] == true AND $this['recurring_span'] == '')
			throw $this->exception('Time gap is required','ValidityCheck')->setField('recurring_span');

		if($this['remind_unit']=='Minutes' && $this['snooze_duration'] && $this['snooze_duration']<30){
			throw $this->exception('Cannot set less then 30 Minutes','ValidityCheck')->setField('snooze_duration');
		}

		if($this['snooze_duration'] && $this['snooze_duration'] == 0){
			throw $this->exception('Cannot be set 0','ValidityCheck')->setField('snooze_duration');
		}

	}

	function addFollowups($app,$contact_id,$followup_tab){
		$followup_tab->add('View');

		$followups_model = $followup_tab->add('xepan\projects\Model_Task');
	    $followups_model->addCondition('related_id',$contact_id);
	    $followups_model->addCondition('type','Followup');
	    $followups_model->addCondition('status','<>','Completed');
	    $followups_model->setOrder('starting_date','asc');

		$followups_crud = $followup_tab->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList','grid_options'=>['del_action_wrapper'=>true]]);
		$followups_crud->setModel($followups_model);
		$followups_crud->grid->template->trySet('task_view_title','FollowUps');
		$followups_crud->grid->addPaginator(10);

	}

	function canUserDelete(){
		if(!$this->force_delete){
			if(($this['type'] != 'Reminder') && ($this['created_by_id'] == $this->app->employee->id) && ($this['assign_to_id']!= $this->app->employee->id) && (!in_array($this['status'],['Completed','Assigned'])) && $this['assign_employee_status'] != "InActive")
				throw $this->exception("You are not authorized to delete this task")
						->addMoreInfo('type',$this['type'])
						->addMoreInfo('created_by',$this['created_by'])
						->addMoreInfo('assign_to_id',$this['assign_to_id'])
						->addMoreInfo('status',$this['status'])
						->addMoreInfo('assign_employee_status',$this['assign_employee_status'])
						;
		}
	}

	function checkExistingFollwerTaskAssociation(){
		$this->ref('xepan\projects\Follower_Task_Association')->each(function($m){$m->delete();});
	}
	
	function checkExistingComment(){
		$this->ref('xepan\projects\Comment')->each(function($m){$m->delete();});
	}
	
	function closeTimesheet(){
		$this->add('xepan\projects\Model_Timesheet')
			 ->dsql()->set('endtime',$this->app->now)
			 ->where('endtime',null)
			 ->where('task_id',$this->id)
			 ->update();
	}

	function checkExistingTimeSheet(){		
		$this->add('xepan\projects\Model_Timesheet')->dsql()->set('task_id',null)->where('task_id',$this->id)->update();
	}

	function checkExistingTaskAttachment(){
		$this->ref('xepan\projects\Task_Attachment')->each(function($m){$m->delete();});
	}

	function notifyAssignement(){
		if($this->dirty['assign_to_id'] and $this['assign_to_id']){
			
			$model_emp = $this->add('xepan\hr\Model_Employee');
			$model_emp->loadBy('id',$this['assign_to_id']);
			if($model_emp->loaded())
				$emp_name = $model_emp['name'];

			$model_emp->loadBy('id',$this['created_by_id']);
			if($model_emp->loaded())
				$created_by = $model_emp['name'];
			
			$assigntask_notify_msg = ['title'=>'New task','message'=>" Task Assigned to you : '" . $this['task_name'] ."' by '". $created_by ."' ",'type'=>'info','sticky'=>true,'desktop'=>true];
			
			$this->app->employee
	            ->addActivity("Task '".$this['task_name']."' assigned to '". $emp_name ."'",null, $this['created_by_id'] /*Related Contact ID*/,null,null,null)
	            ->notifyTo([$this['assign_to_id']],$assigntask_notify_msg); 
		}
	}

	function submit(){

		if($this['is_regular_work']) 
			throw $this->exception('This is regular work and cannot be submitted');

		$this['status']='Submitted';
		$this['updated_at']=$this->app->now;
		$this['submitted_at']=$this->app->now;
		$this->save();
		
		$model_close_timesheet = $this->add('xepan\projects\Model_Timesheet');
		$model_close_timesheet->addCondition('task_id',$this->id);
		$model_close_timesheet->addCondition('employee_id',$this->app->employee->id);
		$model_close_timesheet->addCondition('endtime',null);
		$model_close_timesheet->tryLoadAny();

		if($model_close_timesheet->loaded()){
				$model_close_timesheet['endtime'] = $this->app->now;
				$model_close_timesheet->saveAndUnload();
		}
		
		if($this['assign_to_id']){
			$this->app->employee
		              ->addActivity("Task '".$this['task_name']."' submitted by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		              ->notifyTo([$this['created_by_id']],"Task : '" . $this['task_name'] ."' Submitted by '".$this->app->employee['name']."'");
		}
			
	 	$this->app->page_action_result = $this->app->js(true,$this->app->js()->_selector('.xepan-mini-task')->trigger('reload'))->_selector('.task-waiting-for-approval')->trigger('reload');
	}

	function receive(){
		// throw new \Exception($this->id." = ".$this['status']);

		if($this['assign_to_id'] != $this->app->employee->id){
			$this->app->js()->univ()->errorMessage('You cannot receive task, not assigned to you')->execute();
		}
		
		$this['status']='Pending';
		$this['updated_at']=$this->app->now;
		$this['received_at']=$this->app->now;
		$this->save();
		
		if($this['assign_to_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' received by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id']],"Task : '".$this['task_name']."' Received by '".$this->app->employee['name']."'");
		}	

		return true;
	}

	function reject(){
		$this->isRejecting = true;
		$this['status']='Pending';
		$this['updated_at']=$this->app->now;
		$this['rejected_at']=$this->app->now;
		$this['assign_to_id']=$this['created_by_id'];
		$this->save();
		
		if($this['assign_to_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' rejected by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id']],"Task :'".$this['task_name']."' Rejected by '".$this->app->employee['name']."'");
		}

		return true;	
	}

	function page_reset_deadline($p){
		if($this['created_by_id'] != $this->app->employee->id){			
			 $p->add('View')->set('Sorry you have to ask assignee to change deadline');
			return;
		}

		$form = $p->add('Form');
		$form->addField('DateTimePicker','deadline');
		$form->addSubmit('Save');
			
		if($form->isSubmitted()){
			$this->reset_deadline($form['deadline']);
			$this->app->employee
			          ->addActivity("Task '".$this['task_name']."' deadline changed by'".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
			          ->notifyTo([$this['created_by_id'],$this['assign_to_id']],"Task : ".$this['task_name']."' deadline changed by'".$this->app->employee['name']."'");

			return $this->app->page_action_result = $this->app->js(true,$p->js()->univ()->closeDialog())->univ()->successMessage('Done');
		}
	}

	function reset_deadline($deadline){
		if($deadline){			
			$this['deadline'] = $deadline;
			$this->save();
		} 
	}

	function page_mark_Complete($p){
		if($this['type'] =='Followup'){

			$btn = $p->add('Button')->set('Immediate Complete')->addClass('btn btn-primary xepan-push-large btn-block');
			
			if($btn->isClicked()){				
				$this->mark_complete();
				$this->app->employee
			            ->addActivity("Task '".$this['task_name']."' completed by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
			            ->notifyTo([$this['created_by_id'],$this['assign_to_id']],"Task : ".$this['task_name']."' marked Complete by '".$this->app->employee['name']."'");
				return $this->app->page_action_result = $this->app->js(true,$p->js()->univ()->closeDialog())->_selector('.xepan-mini-task')->trigger('reload');
			}

			$contact = $this->add('xepan\base\Model_Contact');
			$contact->tryLoad($this['related_id']);

			if($contact->loaded()){
				$p->add('View')->setClass('alert alert-info')->set('Add Communication with '. $contact['name_with_type']);
				$comm = $p->add('xepan\communication\View_Communication',['showFilter'=>false]);
				$comm->add('H5',null,'filter')->set('Complete Followup by creating Communication: What is status of This Followup');
				$comm->setCommunicationsWith($this->ref('related_id'));
				$comm->showCommunicationHistory(false);
				$comm->addSuccessJs($this->app->js(null,$p->js()->univ()->closeDialog())->_selector('.xepan-mini-task, .xepan-tasklist-grid')->trigger('reload'));
				$this->app->addHook('communication_created',function($app)use($p){
					$this->mark_complete();
				});
			}else{
				$p->add('View')->setClass('alert alert-danger')->set('Associated Contact not found or removed');
			}

		}else{
			$form = $p->add('Form');
			$form->addField('text','comment');
			$form->addSubmit('Save');
				
			if($form->isSubmitted()){			
				$this->mark_complete();
				// if($this['assign_to_id'] == $this['created_by_id']){
				// $this->app->employee
			 //            ->addActivity("Task '".$this['task_name']."' completed by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null);
				// }else{
				// 	$this->app->employee
				//             ->addActivity("Task '".$this['task_name']."' completed by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
				//             ->notifyTo([$this['created_by_id'],$this['assign_to_id']],"Task : ".$this['task_name']."' marked Complete by '".$this->app->employee['name']."'");
				// }

				return $this->app->page_action_result = $this->app->js(true,$p->js()->univ()->closeDialog())->_selector('.xepan-mini-task')->trigger('reload');
			}
		}

	}

	function mark_complete(){		
		// if($form instanceOf \xepan\communication\Form_Communication){			
		// 	$form->process();
		// }

		// if($form != null AND (!$form instanceOf \xepan\communication\Form_Communication)){
		// 	$comment = $this->add('xepan\projects\Model_Comment');
		// 	$comment['task_id'] = $this->id;
		// 	$comment['employee_id'] = $this->app->employee->id;
		// 	$comment['comment'] = $form['comment'];
		// 	$comment->save();
		// }

		$model_close_timesheet = $this->add('xepan\projects\Model_Timesheet');
		$model_close_timesheet->addCondition('task_id',$this->id);
		$model_close_timesheet->addCondition('employee_id',$this->app->employee->id);
		$model_close_timesheet->addCondition('endtime',null);
		$model_close_timesheet->tryLoadAny();

		if($model_close_timesheet->loaded()){
				$model_close_timesheet['endtime'] = $this->app->now;
				$model_close_timesheet->saveAndUnload();
		} 

		$this['status']='Completed';
		$this['updated_at']=$this->app->now;
		$this['completed_at']=$this->app->now;
		$this->save();
		
		if($this['assign_to_id'] == $this['created_by_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' completed by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null);
		}else{
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' completed by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id'],$this['assign_to_id']],"Task : ".$this['task_name']."' marked Complete by '".$this->app->employee['name']."'");
		}

	 	$this->app->page_action_result = $this->app->js()->_selector('.xepan-mini-task')->trigger('reload');
	}

	function page_reopen($p){
		$form = $p->add('Form');
		$form->addField('text','comment');
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->reopen($form['comment']);
			if($this['assign_to_id']){
				$this->app->employee
			            ->addActivity("Task '".$this['task_name']."' reopen by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
			            ->notifyTo([$this['assign_to_id']],"Task : '".$this['task_name']."' ReOpenned by '".$this->app->employee['name']."' Due To Reason : '".$form['comment']."'");
			}
			return $p->js()->univ()->closeDialog();
		}
	}

	function reopen($comment_text){		
		if($comment_text){
			$comment = $this->add('xepan\projects\Model_Comment');
			$comment['task_id'] = $this->id;
			$comment['employee_id'] = $this->app->employee->id;
			$comment['comment'] = $comment_text;
			$comment->save();
		}

		$this['status'] = 'Pending';
		$this['updated_at']=$this->app->now;
		$this['reopened_at']=$this->app->now;
		$this->save();
	}

	function stop_recurrence(){
		if($this['is_recurring'] && $this['created_by_id'] == $this->app->employee->id){
			$this['is_recurring'] = false;
			$this->save();
		}
		else
			$this->app->js()->univ()->alert('Cant perform this action')->execute();
	}

	function stop_reminder(){		
		if($this['set_reminder'] OR ($this['snooze_duration'] != null OR $this['snooze_duration'] != 0)){
			$this['is_reminded'] = true;
			$this['snooze_duration'] = null;
			$this['set_reminder'] = false;
			$this->save();
		}
		else
			$this->app->js()->univ()->alert('Reminder not setted')->execute();
	}

	function getAssociatedfollowers(){
		$associated_followers = $this->ref('xepan\projects\Follower_Task_Association')
								->_dsql()->del('fields')->field('assign_to_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_followers)),false);
	}

	function removeAssociateFollowers(){
		$this->ref('xepan\projects\Follower_Task_Association')->deleteAll();
	}

	function reminder(){
		$debug = $_GET['cron_debug'];

		$reminder_task = $this->add('xepan\projects\Model_Task');
		$reminder_task->addCondition('set_reminder',true);
		$reminder_task->addCondition([['is_reminded',0],['is_reminded',null]]);

		$config_m = $this->add('xepan\projects\Model_Config_ReminderAndTask');
		$config_m->tryLoadAny();

		foreach ($reminder_task as $task) {
			if(($task['type'] == 'Task' || $task['type'] == 'Followup') AND $task['status'] == 'Completed'){
				$task['is_reminded'] = true;
				$task->saveAs('xepan\projects\Model_Task');
				continue;
			}

			try{

				if($debug) echo "inside reminder task loop<br/>";

				$reminder_time = $task['reminder_time'];
				
				if((strtotime($reminder_time) <= strtotime($this->app->now)) AND $task['is_reminded']==false){
					$remind_via_array = [];
					$remind_via_array = explode(',', $task['remind_via']);

					$employee_array = [];
					$employee_array = explode(',', $task['notify_to']);

					$emails = [];
					$mobile_nos = [];
					foreach ($employee_array as $value){
						if(!$value) continue; // in case user kept 'Please select' also
						$emp = $this->add('xepan\hr\Model_Employee')->tryLoad($value);

						if($emp['status'] != 'Active') continue;
						$temp = explode("<br/>",$emp['emails_str']);
						$emails = array_merge($emails,$temp);

						$contacts = explode("<br/>",$emp['contacts_str']);
						$mobile_nos = array_merge($mobile_nos,$contacts);
					}

					$merge_model_array=[];
					$merge_model_array = array_merge($merge_model_array,$task->get());

					if(in_array("Email", $remind_via_array)){

						$to_emails = implode(', ', $emails);
						if($debug) echo "Sending Emails to ".$to_emails."<br/>";
						
						$email_settings = $this->add('xepan\communication\Model_Communication_DefaultEmailSetting');
						$email_settings->tryLoadAny();

						if($debug) echo "Sending Emails from id ".$email_settings['name']."<br/>";
						
						$mail = $this->add('xepan\projects\Model_ReminderMail');

						// $config_m = $this->add('xepan\base\Model_ConfigJsonModel',
						// [
						// 	'fields'=>[
						// 				'reminder_subject'=>'Line',
						// 				'reminder_body'=>'xepan\base\RichText',
						// 				],
						// 		'config_key'=>'EMPLOYEE_REMINDER_RELATED_EMAIL',
						// 		'application'=>'projects'
						// ]);
						$email_subject = $config_m['reminder_subject'];
	        			$email_body = $config_m['reminder_body'];
							
						$temp=$this->add('GiTemplate');
						$temp->loadTemplateFromString($email_body);
						

						$subject_temp=$this->add('GiTemplate');
						$subject_temp->loadTemplateFromString($email_subject);
						$subject_v=$this->add('View',null,null,$subject_temp);
						$subject_v->template->trySetHtml($merge_model_array);

						$body_v=$this->add('View',null,null,$temp);
						$body_v->template->trySetHtml($merge_model_array);
						// $body_v->template->trySetHTML('task',$task['task_name']);
						// $body_v->template->trySetHTML('description',$task['description']);
						// $body_v->template->trySetHTML('name',$task['employee']);
						
						$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);
						foreach ($emails as  $email) {
							$mail->addTo($email);
						}
						
						$mail->setSubject($subject_v->getHtml());
						$mail->setBody($body_v->getHtml());


						try{
							$mail->send($email_settings,null,false);
							if($debug) echo "Emails Sent <br/>";
						}catch(\Exception $e){
							echo $email_settings['name'];
							if($debug) echo "Emails Not Sent ".$e->getMessage()."<br/>";
						}
					}

					$sms_setting = $this->add('xepan\communication\Model_Communication_DefaultSMSSetting')->tryLoadAny();
					if(in_array("SMS", $remind_via_array) && $sms_setting->loaded()){

						$sms_content = $config_m['reminder_sms_content'];
						$template=$this->add('GiTemplate');
						$template->loadTemplateFromString($sms_content);
						$v = $this->add('View',null,null,$template);
						$v->template->trySetHtml($merge_model_array);
						$sms_content = $v->getHtml();

						$communication = $this->add('xepan\communication\Model_Communication_SMS');
						$communication->addCondition('status','Commented');

						$communication['from_id'] = $task['created_by_id'];
						$communication['direction'] = 'Out';
						$communication['description'] = $sms_content;
						
						foreach ($mobile_nos as $nos) {
							$communication->addTo($nos);
						}
						$communication->setFrom($task['created_by_id'],$task['created_by_id']);
						$communication['title'] = substr($sms_content,0,35)." ...";
						$communication['created_at'] = $this->app->now;
						$communication['communication_channel_id'] = $sms_setting->id;
						$communication->send($sms_setting);

						if($debug) echo "SMS (".$sms_content.") send to ".implode(",", $mobile_nos)."<br/>";
					}

					if(in_array("Notification", $remind_via_array)){					
						$notify_to = json_encode($employee_array);

						$activity = $this->add('xepan\base\Model_Activity');
						$activity['notify_to'] = $notify_to;

						if($task['type'] == 'Task') 
							$activity['notification'] = "Task reminder for: ".$task['task_name'];
						
						if($task['type'] == 'Followup')
							$activity['notification'] = "Followup Reminder For: ".$task['task_name'].' :: Related Contact:'.$task['related_name'];
		

						if($task['type'] == 'Reminder') 
							$activity['notification'] = "Reminder Alert: ".$task['task_name'];

						$activity['Created_at'] = $reminder_time;
						$activity->save();  
					}

					if($task['type'] == 'Reminder' OR (($task['type'] == 'Task' OR $task['type'] == 'Followup') And ($task['snooze_duration'] == null OR $task['snooze_duration'] == 0))){
						$task['is_reminded'] = true;
						$task->saveAs('xepan\projects\Model_Task');

						if($debug) echo "Mark Completed <br/>";
					}else{
						$reminder_time = date("Y-m-d H:i:s", strtotime('+'.$task['snooze_duration'].' '.$task['remind_unit'], strtotime($task['reminder_time'])));
						$task['reminder_time'] = $reminder_time;
						$task->saveAs('xepan\projects\Model_Task');
						if($debug) echo "Setted new time for recurring task<br/>";
					}	
				}
			}catch(\Exception $e){
				if($debug) throw $e;
				continue;
			}
		}
	}

	function recurring(){		
		$recurring_task = $this->add('xepan\projects\Model_Task');
		$recurring_task->addCondition('is_recurring',true);
		$recurring_task->addCondition('starting_date','<=',$this->app->now);

		foreach ($recurring_task as $task) {
			if($this->IsEmployeeInactive($task['created_by_id'],$task['assign_to_id']))
				return;				

			$model_task = $this->add('xepan\projects\Model_Task');
			$model_task['project_id'] = $task['project_id']; 
			$model_task['task_name']  = $task['task_name'];
			$model_task['assign_to_id'] = $task['assign_to_id'];
			$model_task['description'] = $task['description'];
			$model_task['status'] = 'Assigned';
			$model_task['created_at'] = $this->app->now;
			$model_task['priority'] = $task['priority'];
			$model_task['estimate_time'] = $task['estimate_time'];
			$model_task['type'] = $task['type'];
			$model_task['snooze_duration'] = $task['snooze_duration'];
			$model_task['type'] = $task['type'];
			$model_task['remind_via'] = $task['remind_via'];
			$model_task['set_reminder'] = $task['set_reminder'];
			$model_task['notify_to'] = $task['notify_to'];
			$model_task['remind_value'] = $task['remind_value'];
			$model_task['remind_unit'] = $task['remind_unit'];
			$model_task['is_recurring'] = $task['is_recurring'];
			$model_task['recurring_span'] = $task['recurring_span'];
			$model_task['created_by_id'] = $task['created_by_id'];
			$model_task['is_reminder_only'] = $task['is_reminder_only'];

			switch ($task['recurring_span']) {
				case 'Weekely':
					$new_deadline = date("Y-m-d H:i:s", strtotime('+ 1 Weeks', strtotime($task['deadline'])));
					$starting = date("Y-m-d H:i:s", strtotime('+ 1 Weeks', strtotime($task['starting_date'])));
					$reminder = date("Y-m-d H:i:s", strtotime('+ 1 Weeks', strtotime($task['reminder_time'])));
					break;
				case 'Fortnight':
					$new_deadline = date("Y-m-d H:i:s", strtotime('+ 2 Weeks', strtotime($task['deadline'])));
					$starting = date("Y-m-d H:i:s", strtotime('+ 2 Weeks', strtotime($task['starting_date'])));
					$reminder = date("Y-m-d H:i:s", strtotime('+ 2 Weeks', strtotime($task['reminder_time'])));
					break;
				case 'Monthly':
					$new_deadline = date("Y-m-d H:i:s", strtotime('+ 1 months', strtotime($task['deadline'])));
					$starting = date("Y-m-d H:i:s", strtotime('+ 1 months', strtotime($task['starting_date'])));
					$reminder = date("Y-m-d H:i:s", strtotime('+ 1 months', strtotime($task['reminder_time'])));
					break;
				case 'Quarterly':
					$new_deadline = date("Y-m-d H:i:s", strtotime('+ 4 months', strtotime($task['deadline'])));
					$starting = date("Y-m-d H:i:s", strtotime('+ 4 months', strtotime($task['starting_date'])));
					$reminder = date("Y-m-d H:i:s", strtotime('+ 4 months', strtotime($task['reminder_time'])));
					break;
				case 'Halferly':
					$new_deadline = date("Y-m-d H:i:s", strtotime('+ 6 months', strtotime($task['deadline'])));
					$starting = date("Y-m-d H:i:s", strtotime('+ 6 months', strtotime($task['starting_date'])));
					$reminder = date("Y-m-d H:i:s", strtotime('+ 6 months', strtotime($task['reminder_time'])));
					break;
				case 'Yearly':
					$new_deadline = date("Y-m-d H:i:s", strtotime('+ 12 months', strtotime($task['deadline'])));
					$starting = date("Y-m-d H:i:s", strtotime('+ 12 months', strtotime($task['starting_date'])));
					$reminder = date("Y-m-d H:i:s", strtotime('+ 12 months', strtotime($task['reminder_time'])));
					break;					
				default:
					$new_deadline = date("Y-m-d H:i:s", strtotime('+ 1 day', strtotime($task['deadline'])));
					$starting = date("Y-m-d H:i:s", strtotime('+ 1 day', strtotime($task['starting_date'])));
					$reminder = date("Y-m-d H:i:s", strtotime('+ 1 day', strtotime($task['reminder_time'])));
					break;
			}
			
			// deducting snooze time of old remind_time to get exact reminder_time 
			$reminder_time = date("Y-m-d H:i:s", strtotime('+'.$task['snooze_duration'].' '.$task['remind_unit'], strtotime($task['reminder_time'])));

			$model_task['deadline'] = $new_deadline;
			$model_task['starting_date'] = $starting;
			$model_task['reminder_time'] = $reminder;
			$model_task->isRejecting = true;
			$model_task->saveAndUnload();

			$task['is_recurring'] = false;
			$task->saveAs('xepan\projects\Model_Task');
		}

	}

	function IsEmployeeInactive($creator_id = null, $assignee_id = null){
		$emp = $this->add('xepan\hr\Model_Employee');
		$emp->addCondition('status','InActive');
		$emp->addCondition('id',[$creator_id,$assignee_id]);
		$emp->tryLoadAny();

		if($emp->loaded())
			return true;

		return false;
	}


	function myTask(){

		$task_model = $this;//->add('xepan\projects\Model_Task')->load($this->id);

		return (
			(
				$task_model['created_by_id']== $this->app->employee->id 
				&& $task_model['assign_to_id'] == null
			) 
			||
			$task_model['assign_to_id'] == $this->app->employee->id);
	}

	function isMyTask(){
		return $this->myTask();
	}

	function createdByMe(){
		return $this['created_by_id'] == $this->app->employee->id;
	}

	function IhaveAssignedToOthers(){
		return $this->createdByMe() && !$this->myTask();
	}

	function iCanPlay(){
		return ($this->myTask() && !in_array($this['status'],['Completed','Submitted']));
	}

	function canStop(){
		return $this->myTask() && $this['status'] == 'Inprogress';
	}

	function canDelete(){
		return $this->myTask() && $this->createdByMe() /*&& in_array($this['status'],['Completed','Submitted'])*/;
	}

	function ICanAssign(){
		if($this->app->auth->model->isSuperUser()) return true;
		return $this->createdByMe() && !in_array($this['status'], ['Inprogress','Completed','Submitted']);
	}

	function ICanReject(){
		$task_model = $this;//->add('xepan\projects\Model_Task')->load($this->id);
		return $this->myTask() && $task_model['status'] == "Assigned";
	}

	function ICanEdit(){
		return $this->createdByMe() && !in_array($this['status'], ['Inprogress','Completed','Submitted']);
	}
}
