<?php

namespace xepan\projects;


class Model_Config_ReminderAndTask extends \xepan\base\Model_ConfigJsonModel{
	public $fields = [
						'reminder_subject'=>'Line',
						'reminder_body'=>'xepan\base\RichText',
						'force_to_fill_sitting_ideal'=>'Checkbox',
						'for_selected_posts'=>'xepan\hr\Post',
						'repeate_check_in_seconds'=>'Number',
						];
	public $config_key='EMPLOYEE_REMINDER_RELATED_EMAIL';
	public $application='projects';

	function init(){
		parent::init();

		$this->getField('force_to_fill_sitting_ideal')->defaultValue(false);
		$this->getField('repeate_check_in_seconds')->defaultValue(60);
		// $this->getField('system_contact_types')->defaultValue('Contact,Customer,Supplier,Employee');
	}

}