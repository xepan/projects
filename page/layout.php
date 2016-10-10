<?php

namespace xepan\projects;

class page_layout extends \xepan\projects\page_configuration{
	public $title = "Layouts";
	function init(){
		parent::init();
		
		$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
		[
			'fields'=>[
						'reminder_subject'=>'Line',
						'reminder_body'=>'xepan\base\RichText',
						],
				'config_key'=>'EMPLOYEE_REMINDER_RELATED_EMAIL',
				'application'=>'projects'
		]);
		
		$config_m->add('xepan\hr\Controller_ACL');
		$config_m->tryLoadAny();

		$form=$this->add('Form');
		$form->setModel($config_m,['reminder_subject','reminder_body']);
		$form->getElement('reminder_subject')->set($config_m['reminder_subject']);
		$form->getElement('reminder_body')->setFieldHint('{$name}, {$task}, {$description}')->set($config_m['reminder_body']);
		$form->addSubmit('Save')->addClass('btn btn-primary');
		
		if($form->isSubmitted()){
			$form->save();
			$form->js(null,$form->js()->reload())->univ()->successMessage('Information Updated')->execute();
		}
	}
}