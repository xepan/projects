<?php

namespace xepan\projects;

class Model_Task extends \xepan\base\Model_Table
{	
	public $table = "task";
	public $title_field ='task_name';

	public $status=['Pending','Completed','Reopened'];

	public $actions =[
		'Submitted'=>['view','edit','delete','mark_complete'],
		'Assigned'=>['view','edit','delete','mark_complete'],
		'Completed'=>['view','edit','delete','re_open'],
		'Pending'=>['view','edit','delete','mark_complete'],
		'On-Hold'=>['view','edit','delete','mark_complete'],
	];
	public $acl=false;
	
	function init()
	{
		parent::init();

		$this->hasOne('xepan\base\Epan');
		$this->hasOne('xepan\projects\Project','project_id');
		$this->hasOne('xepan\hr\Employee','employee_id');
		$this->hasOne('xepan\hr\Employee','created_by_id')->defaultValue($this->app->employee->id);
		
		$this->addField('task_name');
		$employee_model = $this->add('xepan\hr\Model_Employee')->addCondition('status','Active');		
		$this->addField('notify_to')->display(['form'=>'xepan\base\DropDown'])->setModel($employee_model);
		$this->addField('description')->type('text');
		$this->addField('deadline')->display(['form'=>'DateTimePicker'])->type('datetime');
		$this->addField('starting_date')->display(['form'=>'DateTimePicker'])->type('datetime');
		$this->addField('estimate_time')/*->display(['form'=>'TimePicker'])*/;
		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
		$this->addField('status')->defaultValue('Pending');
		$this->addField('type');
		$this->addField('priority')->setValueList(['25'=>'Low','50'=>'Medium','75'=>'High','90'=>'Critical'])->EmptyText('Priority')->defaultValue(50);
		$this->addField('set_reminder')->type('boolean');
		$this->addField('remind_via')->display(['form'=>'xepan\base\DropDown'])->setValueList(['Email'=>'Email','SMS'=>'SMS','Notification'=>'Notification']);
		$this->addField('remind_value')->type('number');
		$this->addField('remind_unit')->setValueList(['Minutes'=>'Minutes','hours'=>'Hours','day'=>'Days','Weeks'=>'Weeks','months'=>'Months']);
		$this->addField('is_recurring')->type('boolean');
		$this->addField('recurring_span')->setValueList(['Weekely'=>'Weekely','Fortnight'=>'Fortnight','Monthly'=>'Monthly','Quarterly'=>'Quarterly','Halferly'=>'Halferly','Yearly'=>'Yearly']);
		$this->addField('is_reminded')->type('boolean');
		$this->addCondition('type','Task');

		$this->hasMany('xepan\projects\Follower_Task_Association','task_id');
		$this->hasMany('xepan\projects\Comment','task_id');	
		$this->hasMany('xepan\projects\Timesheet','task_id');	
		$this->hasMany('xepan\projects\Task_Attachment','task_id');	

		$this->addHook('beforeSave',[$this,'notifyAssignement']);
		$this->addHook('beforeDelete',[$this,'checkExistingFollwerTaskAssociation']);
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
		if($this->dirty['employee_id'] and $this['employee_id']){
			$this->app->employee
	            ->addActivity("Task Assigned", $this->id, $this['created_by_id'] /*Related Contact ID*/)
	            ->notifyTo([$this['employee_id']],"Task Assigend to you : " . $this['task_name']);
		}
	}

	function submit(){
	}


	function mark_complete(){		
		$this['status']='Completed';
		$this->save();
		if($this['employee_id']){
			$this->app->employee
		            ->addActivity("Task Completed", $this->id, $this['employee_id'] /*Related Contact ID*/)
		            ->notifyTo([$this['created_by_id']],"Task Completed : " . $this['task_name']);
		}
	}

	function re_open(){		
		$this['status']='Pending';
		$this->save();
		if($this['employee_id']){
			$this->app->employee
		            ->addActivity("Task ReOpenned", $this->id, $this->app->employee->id /*Related Contact ID*/)
		            ->notifyTo([$this['employee_id']],"Task ReOpenned : " . $this['task_name']);
		}
	}


	function getAssociatedfollowers(){
		$associated_followers = $this->ref('xepan\projects\Follower_Task_Association')
								->_dsql()->del('fields')->field('employee_id')->getAll();
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
						$emp = $this->add('xepan\hr\Model_Employee')->load($value);
						array_push($emails, $emp['first_email']);
					}
					$to_emails = implode(', ', $emails);
					
					$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
					$mail = $this->add('xepan\communication\Model_Communication_Email');

					$email_subject = file_get_contents(realpath(getcwd().'/vendor/xepan/projects/templates/default/reminder_subject.html'));
        			$email_body = file_get_contents(realpath(getcwd().'/vendor/xepan/projects/templates/default/reminder_body.html'));
						
					$temp=$this->add('GiTemplate');
					$temp->loadTemplateFromString($email_body);
				
					$subject_temp=$this->add('GiTemplate');
					$subject_temp->loadTemplateFromString($email_subject);
					$subject_v=$this->add('View',null,null,$subject_temp);

					$body_v=$this->add('View',null,null,$temp);
					$body_v->template->trySetHTML('task',$task['task_name']);
					$body_v->template->trySetHTML('name',$task['employee']);
					
					$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);
					foreach ($emails as  $email) {
						$mail->addTo($email);
					}
					$mail->setSubject($subject_v->getHtml());
					$mail->setBody($body_v->getHtml());
					$mail->send($email_settings);
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
		$recurring_task->addCondition('deadline',$this->app->today);
		
		foreach ($recurring_task as $task) {
			$model_task = $this->add('xepan\projects\Model_Task');
			$model_task['project_id'] = $task['project_id']; 
			$model_task['task_name']  = $task['task_name'];
			$model_task['employee_id'] = $task['employee_id'];
			$model_task['description'] = $task['description'];
			$model_task['starting_date'] = $task['starting_date'];
			$model_task['status'] = $task['status'];
			$model_task['created_at'] = $task['created_at'];
			$model_task['priority'] = $task['priority'];
			$model_task['estimate_time'] = $task['estimate_time'];
			$model_task['set_reminder'] = $task['set_reminder'];
			$model_task['remind_via'] = $task['remind_via'];
			$model_task['remind_value'] = $task['remind_value'];
			$model_task['remind_unit'] = $task['remind_unit'];
			$model_task['is_recurring'] = $task['is_recurring'];
			$model_task['recurring_span'] = $task['recurring_span'];
			$model_task->addCondition('created_by_id',$this->app->employee->id);
			$model_task->save();

			// TWO THINGS LEFT
			// 1. CALCULATING AND SETTING DEADLINE BASED ON RECURRING_SPAN
			// 2. CALLING THIS FUNCTION VIA CRON JOB
		}
	}
}
