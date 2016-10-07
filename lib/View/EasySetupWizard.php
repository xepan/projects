<?php

namespace xepan\projects;

class View_EasySetupWizard extends \View{
	function init(){
		parent::init();
		
		if($_GET[$this->name.'_task_reminder_mail_content']){
			$this->js(true)->univ()->frameURL("Task Reminder Layout",$this->app->url('xepan_projects_layout'));
		}

		$isDone = false;
		
			$action = $this->js()->reload([$this->name.'_task_reminder_mail_content'=>1]);

			// if($this->add('xepan\communication\Model_Communication_EmailSetting')->count()->getOne() > 0){
			// 	$isDone = true;
			// 	$action = $this->js()->univ()->dialogOK("Already have Data",' You already have emailsetting, visit page ? <a href="'. $this->app->url('xepan_communication_general_email')->getURL().'"> click here to go </a>');
			// }

			$task_reminder_view = $this->add('xepan\base\View_Wizard_Step');

			$task_reminder_view->setAddOn('Application - Projects')
				->setTitle('Set Layout For Task Reminder Mail')
				->setMessage('Please set layout for sending mail for remind the task.')
				->setHelpMessage('Need help ! click on the help icon')
				->setHelpURL('#')
				->setAction('Click Here',$action,$isDone);

	}
}