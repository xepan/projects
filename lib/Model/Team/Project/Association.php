<?php

namespace xepan\projects;

class Model_Team_Project_Association extends \xepan\base\Model_Table{
	public $table = "team_project_association";
	function init(){
		parent::init();

		$this->hasOne('xepan\hr\Employee','employee_id');
		$this->hasOne('xepan\projects\Project','project_id');
	}
}