<?php

namespace xepan\projects;

class Model_ReminderMail extends \xepan\communication\Model_Communication_Abstract_Email{
	public $status=['Outbox','Sent'];
	function init(){
		parent::init();
		$this->getElement('status')->defaultValue('Outbox');
		$this->addCondition('communication_type','ReminderEmail');	
		$this->addCondition('direction','Out');	
	}
}
