<?php
namespace xepan\projects;

class page_test extends \Page{
	function init(){
		parent::init();

		$form = $this->add('Form');
		$fld = $form->addField('DateTimePicker','time');
		$form->addSubmit('Submit');

		if($form->isSubmitted()){
			throw new \Exception($form['time']);
		}
	}
}