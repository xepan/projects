<?php
namespace xepan\projects;
class page_configuration extends \xepan\base\Page{
	public $title = "Configuration";
	function init(){
		parent::init();		

		$tabs= $this->add('Tabs');
		$tsk_tab = $tabs->addTab('Task Configurations');
		$lay_tab = $tabs->addTab('Layouts');


		$config_m = $this->add('xepan\projects\Model_Config_ReminderAndTask');
		$config_m->tryLoadAny();

		$form=$tsk_tab->add('Form');
		$form->setModel($config_m,['reminder_subject','reminder_body']);
		$form->getElement('reminder_subject')->set($config_m['reminder_subject']);
		$form->getElement('reminder_body')->setFieldHint('{$name}, {$task}, {$description}')->set($config_m['reminder_body']);
		$form->addSubmit('Save')->addClass('btn btn-primary');
		
		if($form->isSubmitted()){
			$form->save();
			$form->js(null,$form->js()->reload())->univ()->successMessage('Information Updated')->execute();
		}

		$form=$lay_tab->add('Form');
		$config_m = $this->add('xepan\projects\Model_Config_ReminderAndTask');
		$config_m->tryLoadAny();

		$form->setModel($config_m,['force_to_fill_sitting_ideal','for_selected_posts']);
		$form->getElement('for_selected_posts')->multiSelect()->set(explode(",",$config_m['for_selected_posts']))->setEmptyText('For All Posts');
		$form->addSubmit('Save')->addClass('btn btn-primary');
		if($form->isSubmitted()){
			$form->save();
			$form->js(null,$form->js()->reload())->univ()->successMessage('Information Updated, will take effect after new login for ever employee')->execute();
		}

		// $this->app->side_menu->addItem(['Layouts','icon'=>'fa fa-th'],'xepan_projects_layout')->setAttr(['title'=>'Layouts']);
		// $this->app->side_menu->addItem(['Task Configurations','icon'=>'fa fa-cog'],'xepan_projects_taskconfig')->setAttr(['title'=>'Task Configurations']);
	}
}