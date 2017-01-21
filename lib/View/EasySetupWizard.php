<?php

namespace xepan\projects;

class View_EasySetupWizard extends \View{
	function init(){
		parent::init();
		
		/**
		Reminder Email Content Wizard
		*/
		if($_GET[$this->name.'_task_reminder_mail_content']){
			$task_config_mdl = $this->add('xepan\base\Model_ConfigJsonModel',
				[
					'fields'=>[
								'reminder_subject'=>'Line',
								'reminder_body'=>'xepan\base\RichText',
								],
						'config_key'=>'EMPLOYEE_REMINDER_RELATED_EMAIL',
						'application'=>'projects'
				]);
				
			$task_config_mdl->tryLoadAny();

			$task_rem_subject = file_get_contents(realpath(getcwd().'/vendor/xepan/projects/templates/default/reminder_subject.html'));
			$task_rem_body = file_get_contents(realpath(getcwd().'/vendor/xepan/projects/templates/default/reminder_body.html'));
		
			if(!$task_config_mdl['reminder_subject']){
				$task_config_mdl['reminder_subject'] = $task_rem_subject;
			}

			if(!$task_config_mdl['reminder_body']){
				$task_config_mdl['reminder_body'] = $task_rem_body;
			}

			$task_config_mdl->save();
			$this->js(true)->univ()->frameURL("Task Reminder Layout",$this->app->url('xepan_projects_layout'));
		}

		$isDone = false;
		
		$action = $this->js()->reload([$this->name.'_task_reminder_mail_content'=>1]);

		$task_config_mdl = $this->add('xepan\base\Model_ConfigJsonModel',
		[
			'fields'=>[
						'reminder_subject'=>'Line',
						'reminder_body'=>'xepan\base\RichText',
						],
				'config_key'=>'EMPLOYEE_REMINDER_RELATED_EMAIL',
				'application'=>'projects'
		]);
		
		$task_config_mdl->tryLoadAny();

		if($task_config_mdl['reminder_subject'] && $task_config_mdl['reminder_body']){
			$isDone = true;
			$action = $this->js()->univ()->dialogOK("Already have Data",' You have already set the layout of reminder mail, visit page ? <a href="'. $this->app->url('xepan_projects_layout')->getURL().'"> click here to go </a>');
		}

		$task_reminder_view = $this->add('xepan\base\View_Wizard_Step');

		$task_reminder_view->setAddOn('Application - Projects')
			->setTitle('Set Layout For Task Reminder Mail')
			->setMessage('Please set layout for sending mail for remind the task.')
			->setHelpMessage('Need help ! click on the help icon')
			->setHelpURL('#')
			->setAction('Click Here',$action,$isDone);

	}
}