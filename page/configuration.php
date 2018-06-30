<?php
namespace xepan\projects;

class page_configuration extends \xepan\base\Page{
	public $title = "Configuration";
	
	function page_index(){

		$tabs= $this->add('Tabs');
		$tsk_tab = $tabs->addTabURL('./task','Task Configurations');
		$lay_tab = $tabs->addTabURL('./layouts','Layouts');
		$lay_tab = $tabs->addTabURL('./tasksubtype','Task Subtype');
		
	}

	function page_layouts(){
		$config_m = $this->add('xepan\projects\Model_Config_ReminderAndTask');
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

	function page_task(){
		$form=$this->add('Form');
		$config_m = $this->add('xepan\projects\Model_Config_ReminderAndTask');
		$config_m->tryLoadAny();

		$form->setModel($config_m,['force_to_fill_sitting_ideal','for_selected_posts','repeate_check_in_seconds','allow_editing_timesheet_in_days']);
		$form->getElement('for_selected_posts')->multiSelect()->set(explode(",",$config_m['for_selected_posts']))->setEmptyText('For All Posts');
		$form->addSubmit('Save')->addClass('btn btn-primary');
		if($form->isSubmitted()){
			$form->save();
			$form->js(null,$form->js()->reload())->univ()->successMessage('Information Updated, will take effect after new login for ever employee')->execute();
		}
	}

	function page_tasksubtype(){
		$this->title = "Task Subtype";
		$config = $this->add('xepan\projects\Model_Config_TaskSubtype');
		$config->tryLoadAny();
		$form = $this->add('Form');
		$form->setModel($config);
		$form->addSubmit('Save');

		if($form->isSubmitted()){
			$form->save();
			$form->js()->univ()->successMessage("Task Subtype Saved Success fully")->execute();
		}


	}
}