<?php
namespace xepan\projects;

class page_test extends \Page{
	function init(){
		parent::init();

		$form = $this->add('Form');
		$form->addField('DropDown','do_what')->setValueList(['CallCron'=>'CallCron','CompatibleToRestructure'=>'CompatibleToRestructure'])->setEmptyText('Please select a value');
		$form->addSubmit('Submit');
		
		if($form->isSubmitted()){
			if($form['do_what'] == 'CallCron')
				$this->CallCron();
							
			if($form['do_what'] == 'CompatibleToRestructure')				
				$this->CompatibleToRestructure();

			$form->js()->reload()->execute();
		}	
	}

	function CallCron(){		
		$task = $this->add('xepan\projects\Model_Task');
		$task->reminder();
		$task->recurring();
	}

	function compatibleToRestructure(){
		$tasks = $this->add('xepan\projects\Model_Task');
		$tasks->addCondition('set_reminder',true);

		foreach ($tasks as $task) {
			$reminder_time = date("Y-m-d H:i:s", strtotime('- '.$task['remind_value'].' '.$task['remind_unit'], strtotime($task['starting_date'])));
			$task['reminder_time'] = $reminder_time;
			$task['snooze_duration'] = null;
			$task->save();
		}
		
	}
}