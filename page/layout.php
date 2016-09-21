<?php

namespace xepan\projects;

class page_layout extends \xepan\projects\page_configuration{
	public $title = "Layouts";
	function init(){
		parent::init();
		
		$reminder_mail_layout = $this->app->epan->config->getConfig('REMINDERLAYOUT');
		$reminder_subject_layout = $this->app->epan->config->getConfig('REMINDERSUBJECTLAYOUT');

		$reminder_form = $this->add('Form');
		$reminder_form->addField('reminder_subject_layout')->set($reminder_subject_layout);
		$reminder_form->addField('xepan\base\RichText','reminder_layout')->set($reminder_mail_layout);
		$reminder_form->addSubmit('Save')->addClass('btn btn-primary');
	
		if($reminder_form->isSubmitted()){
			$this->app->epan->config->setConfig('REMINDERLAYOUT',$reminder_form['reminder_layout'],'projects');
			$this->app->epan->config->setConfig('REMINDERSUBJECTLAYOUT',$reminder_form['reminder_subject_layout'],'projects');
			return $reminder_form->js()->univ()->successMessage('Saved')->execute();
		}
	}
}