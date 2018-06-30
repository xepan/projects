<?php

namespace xepan\projects;


class Model_Config_TaskSubtype extends \xepan\base\Model_ConfigJsonModel{
	public $fields = [
					'value'=>'Text',
				];
	public $config_key='PROJECT_TASK_SUBTYPE';
	public $application='projects';

	function init(){
		parent::init();

		$this->getField('value')->hint('comma(,) seperated multiple value without space i.e. Meeting,Call,Regular');
	}

}