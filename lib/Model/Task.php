<?php

namespace xepan\projects;

class Model_Task extends \xepan\base\Model_Table
{	
	public $table = "task";
	public $title_field ='task_name';
	public $acl = false;
	public $status=['Pending','Submitted','Completed','Assigned','Inprogress'];
	
	public $actions=[
		'Pending'=>['submit','mark_complete'],
		'Inprogress'=>['submit','mark_complete'],
		'Assigned'=>['receive','reject'],
		'Submitted'=>['mark_complete','reopen'],
		'Completed'=>[]
	];

	function init()
	{
		parent::init();

		$this->hasOne('xepan\base\Epan');
		$this->hasOne('xepan\projects\Project','project_id');
		$this->hasOne('xepan\hr\Employee','assign_to_id')->defaultValue($this->app->employee->id);
		$this->hasOne('xepan\hr\Employee','created_by_id')->defaultValue($this->app->employee->id);
		
		$this->addField('task_name');
		$employee_model = $this->add('xepan\hr\Model_Employee')->addCondition('status','Active');		
		$this->addField('notify_to')->display(['form'=>'xepan\base\DropDown'])->setModel($employee_model);
		$this->addField('description')->type('text');
		$this->addField('deadline')->display(['form'=>'DateTimePicker'])->type('datetime')->defaultValue($this->app->now);
		$this->addField('starting_date')->display(['form'=>'DateTimePicker'])->type('datetime')->defaultValue($this->app->now);
		$this->addField('estimate_time')/*->display(['form'=>'TimePicker'])*/;
		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
		$this->addField('status')->defaultValue('Pending');
		$this->addField('updated_at')->type('datetime');
		$this->addField('type');
		$this->addField('priority')->setValueList(['25'=>'Low','50'=>'Medium','75'=>'High','90'=>'Critical'])->EmptyText('Priority')->defaultValue(50);
		$this->addField('set_reminder')->type('boolean');
		$this->addField('remind_via')->display(['form'=>'xepan\base\DropDown'])->setValueList(['Email'=>'Email','SMS'=>'SMS','Notification'=>'Notification']);
		$this->addField('remind_value')->type('number');
		$this->addField('remind_unit')->setValueList(['Minutes'=>'Minutes','hours'=>'Hours','day'=>'Days','Weeks'=>'Weeks','months'=>'Months']);
		$this->addField('is_recurring')->type('boolean');
		$this->addField('recurring_span')->setValueList(['Daily'=>'Daily','Weekely'=>'Weekely','Fortnight'=>'Fortnight','Monthly'=>'Monthly','Quarterly'=>'Quarterly','Halferly'=>'Halferly','Yearly'=>'Yearly']);
		$this->addField('is_reminded')->type('boolean');
		$this->addField('is_reminder_only')->type('boolean')->defaultValue(false);
		$this->addCondition('type','Task');

		$this->hasMany('xepan\projects\Follower_Task_Association','task_id');
		$this->hasMany('xepan\projects\Comment','task_id');	
		$this->hasMany('xepan\projects\Timesheet','task_id');	
		$this->hasMany('xepan\projects\Task_Attachment','task_id');	

		$this->addHook('beforeSave',[$this,'beforeSave']);
		$this->addHook('beforeSave',[$this,'notifyAssignement']);
		$this->addHook('beforeDelete',[$this,'checkExistingFollwerTaskAssociation']);
		$this->addHook('beforeDelete',[$this,'canUserDelete']);
		$this->addHook('beforeDelete',[$this,'checkExistingComment']);
		$this->addHook('beforeDelete',[$this,'checkExistingTimeSheet']);
		$this->addHook('beforeDelete',[$this,'checkExistingTaskAttachment']);

		$this->is([
			'task_name|required'
			]);

		$this->addExpression('follower_count')->set(function($m){
			return $m->refSQL('xepan\projects\Follower_Task_Association')->count();
		});

		$this->setOrder('priority');

 	}
	
	function beforeSave(){		
		if($this['is_reminder_only'] == false && $this->isDirty('assign_to_id')){
			if(!$this->ICanAssign() and !$this->ICanReject())
				throw $this->exception('Cannot assign running task','ValidityCheck')
							->setField('assign_to_id');
		}

		if(!$this['id'] && $this['assign_to_id'] && $this['assign_to_id'] != $this['created_by_id']){
			$this['status'] = "Assigned";
		}
		$this['updated_at'] = $this->app->now;

		if((!$this['set_reminder']) && ($this['deadline'] < $this['starting_date'])){
			throw $this->exception('Deadline can not be smaller then starting date','ValidityCheck')->setField('deadline');
		}
	}

	function canUserDelete(){
		if(($this['is_reminder_only'] == false) && ($this['created_by_id'] == $this->app->employee->id) && ($this['assign_to_id']!= $this->app->employee->id) && $this['status'] != 'Completed')
			throw new \Exception("You are not authorized to delete this task");		
	}

	function checkExistingFollwerTaskAssociation(){
		$this->ref('xepan\projects\Follower_Task_Association')->each(function($m){$m->delete();});
	}
	
	function checkExistingComment(){
		$this->ref('xepan\projects\Comment')->each(function($m){$m->delete();});
	}
	
	function checkExistingTimeSheet(){
		$this->ref('xepan\projects\Timesheet')->each(function($m){$m->delete();});
	}
	function checkExistingTaskAttachment(){
		$this->ref('xepan\projects\Task_Attachment')->each(function($m){$m->delete();});
	}

	function notifyAssignement(){
		if($this->dirty['assign_to_id'] and $this['assign_to_id']){
			$this->app->employee
	            ->addActivity("Task '".$this['task_name']."' assigned to '".$this['assign_to_id']."'",null, $this['created_by_id'] /*Related Contact ID*/,null,null,null)
	            ->notifyTo([$this['assign_to_id']],"Task Assigned to you : " . $this['task_name']);
		}
	}

	function submit(){
		$this['status']='Submitted';
		$this['updated_at']=$this->app->now;
		$this->save();
		
		$model_close_timesheet = $this->add('xepan\projects\Model_Timesheet');
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
		            ->notifyTo([$this['created_by_id']],"Task Submitted : " . $this['task_name']);
		}
		
	 	$this->app->page_action_result = $this->app->js()->_selector('.xepan-mini-task')->trigger('reload');
	}

	function receive(){
		// throw new \Exception($this->id." = ".$this['status']);
		
		$this['status']='Pending';
		$this['updated_at']=$this->app->now;
		$this->save();
		
		if($this['assign_to_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' received by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id']],"Task Received : " . $this['task_name']);
		}	

		return true;
	}

	function reject(){

		$this['status']='Pending';
		$this['updated_at']=$this->app->now;
		$this['assign_to_id']=$this['created_by_id'];
		$this->save();
		
		if($this['assign_to_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' rejected by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id']],"Task Rejected : " . $this['task_name']);
		}

		return true;	
	}

	function mark_complete(){		
		$this['status']='Completed';
		$this['updated_at']=$this->app->now;
		$this->save();
		
		$model_close_timesheet = $this->add('xepan\projects\Model_Timesheet');
		$model_close_timesheet->addCondition('employee_id',$this->app->employee->id);
		$model_close_timesheet->addCondition('endtime',null);
		$model_close_timesheet->tryLoadAny();

		if($model_close_timesheet->loaded()){
				$model_close_timesheet['endtime'] = $this->app->now;
				$model_close_timesheet->saveAndUnload();
		}
		
		if($this['assign_to_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' completed by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id']],"Task Completed : " . $this['task_name']);
		}

	 	$this->app->page_action_result = $this->app->js()->_selector('.xepan-mini-task')->trigger('reload');
	}

	function reopen(){		
		$this['status']='Pending';
		$this['updated_at']=$this->app->now;
		$this->save();
		if($this['assign_to_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' reopen by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['assign_to_id']],"Task ReOpenned : " . $this['task_name']);
		}
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
		
		$reminder_task = $this->add('xepan\projects\Model_Task');
		$reminder_task->addCondition('set_reminder',true);
		$reminder_task->addCondition('is_reminded',null);

		foreach ($reminder_task as $task) {	
							
			$reminder_time = date("Y-m-d H:i:s", strtotime('-'.$task['remind_value'].' '.$task['remind_unit'], strtotime($task['starting_date'])));

			if(($reminder_time <= ($this->app->now)) AND $task['is_reminded']==false){
				
				$remind_via_array = [];
				$remind_via_array = explode(',', $task['remind_via']);

				$employee_array = [];
				$employee_array = explode(',', $task['notify_to']);

				if(in_array("Email", $remind_via_array)){
					$emails = [];
					foreach ($employee_array as $value){
						if(!$value) continue; // in case user kept 'Please select' also
						$emp = $this->add('xepan\hr\Model_Employee')->load($value);
						array_push($emails, $emp['first_email']);
					}
					$to_emails = implode(', ', $emails);
					$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting');
					$email_settings->addCondition('is_active',true);
					$email_settings->tryLoadAny();	
					
					$mail = $this->add('xepan\communication\Model_Communication_Email');

					$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'reminder_subject'=>'Line',
									'reminder_body'=>'xepan\base\RichText',
									],
							'config_key'=>'EMPLOYEE_REMINDER_RELATED_EMAIL',
							'application'=>'projects'
					]);
					$config_m->tryLoadAny();

					$email_subject = $config_m['reminder_subject'];
        			$email_body = $config_m['reminder_body'];
						
					$temp=$this->add('GiTemplate');
					$temp->loadTemplateFromString($email_body);
					

					$merge_model_array=[];
					$merge_model_array = array_merge($merge_model_array,$task->get());

					$subject_temp=$this->add('GiTemplate');
					$subject_temp->loadTemplateFromString($email_subject);
					$subject_v=$this->add('View',null,null,$subject_temp);

					$subject_v->template->trySetHTML('task',$task['task_name']);

					$body_v=$this->add('View',null,null,$temp);
					$body_v->template->trySet($merge_model_array);
					$body_v->template->trySetHTML('task',$task['task_name']);
					$body_v->template->trySetHTML('description',$task['description']);
					$body_v->template->trySetHTML('name',$task['employee']);
					
					$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);
					foreach ($emails as  $email) {
						$mail->addTo($email);
					}
					
					$mail->setSubject($subject_v->getHtml());
					$mail->setBody($body_v->getHtml());
					try{
						$mail->send($email_settings);
					}catch(\Exception $e){
						echo $email_settings['name'];
					}
				}

				if(in_array("SMS", $remind_via_array)){
					// SMS CONFIGURATION REMAINING
				}

				if(in_array("Notification", $remind_via_array)){					
					$notify_to = json_encode($employee_array);

					$activity = $this->add('xepan\base\Model_Activity');
					$activity['notify_to'] = $notify_to; 
					$activity['notification'] = "Task reminder for: ".$task['task_name'];
					$activity['Created_at'] = $reminder_time;
					$activity->save();  
				}

					$task['is_reminded'] = true;
					$task->saveAs('xepan\projects\Model_Task');
			}
		}
	}

	function recurring(){		
		$recurring_task = $this->add('xepan\projects\Model_Task');
		$recurring_task->addCondition('is_recurring',true);
		$recurring_task->addCondition('starting_date','<=',$this->app->now);

		foreach ($recurring_task as $task) {
			
			$model_task = $this->add('xepan\projects\Model_Task');
			$model_task['project_id'] = $task['project_id']; 
			$model_task['task_name']  = $task['task_name'];
			$model_task['assign_to_id'] = $task['assign_to_id'];
			$model_task['description'] = $task['description'];
			$model_task['status'] = $task['status'];
			$model_task['created_at'] = $task['created_at'];
			$model_task['priority'] = $task['priority'];
			$model_task['estimate_time'] = $task['estimate_time'];
			$model_task['is_reminder_only'] = $task['is_reminder_only'];
			$model_task['remind_via'] = $task['remind_via'];
			$model_task['set_reminder'] = $task['set_reminder'];
			$model_task['notify_to'] = $task['notify_to'];
			$model_task['remind_value'] = $task['remind_value'];
			$model_task['remind_unit'] = $task['remind_unit'];
			$model_task['is_recurring'] = $task['is_recurring'];
			$model_task['recurring_span'] = $task['recurring_span'];
			$model_task['created_by_id'] = $task['created_by_id'];
 
			switch ($task['recurring_span']) {
				case 'Weekely':
					$starting = date("Y-m-d H:i:s", strtotime('+ 1 Weeks', strtotime($task['starting_date'])));
					break;
				case 'Fortnight':
					$starting = date("Y-m-d H:i:s", strtotime('+ 2 Weeks', strtotime($task['starting_date'])));
					break;
				case 'Monthly':
					$starting = date("Y-m-d H:i:s", strtotime('+ 1 months', strtotime($task['starting_date'])));
					break;
				case 'Quarterly':
					$starting = date("Y-m-d H:i:s", strtotime('+ 4 months', strtotime($task['starting_date'])));
					break;
				case 'Halferly':
					$starting = date("Y-m-d H:i:s", strtotime('+ 6 months', strtotime($task['starting_date'])));
					break;
				case 'Yearly':
					$starting = date("Y-m-d H:i:s", strtotime('+ 12 months', strtotime($task['starting_date'])));
					break;					
				
				default:
					$starting = date("Y-m-d H:i:s", strtotime('+ 1 day', strtotime($task['starting_date'])));
					break;
			}
			
			$new_deadline = date("Y-m-d H:i:s", strtotime('+ 1 day', strtotime($starting)));
			$model_task['deadline'] = $new_deadline;
			$model_task['starting_date'] = $starting;
			$model_task->saveAndUnload();

			$task['is_recurring'] = false;
			$task->saveAs('xepan\projects\Model_Task');
		}

	}

	function myTask(){
		$task_model = $this->add('xepan\projects\Model_Task')->load($this->id);

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
		return $this->createdByMe() && !in_array($this['status'], ['Inprogress','Completed','Submitted']);
	}

	function ICanReject(){
		$task_model = $this->add('xepan\projects\Model_Task')->load($this->id);
		return $this->myTask() && $task_model['status'] == "Assigned";
	}

	function ICanEdit(){
		return $this->createdByMe() && !in_array($this['status'], ['Inprogress','Completed','Submitted']);
	}
}
