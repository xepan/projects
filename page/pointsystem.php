<?php

namespace xepan\projects;

class page_pointsystem extends \xepan\projects\page_sidemenu{
	public $title = "The Point System";
	
	function page_index(){
		
		$this->app->page_top_right_button_set
				->addButton('Rule Groups')
				->addClass('btn btn-primary')
				->js('click')->univ()->frameURL('Manage Rule Groups',$this->app->url('./rulegroups'));
				;

		$rules_m = $this->add('xepan\base\Model_Rules');

		$crud = $this->add('xepan\hr\CRUD');
		$crud->setModel($rules_m);

		$crud->noAttachment();

		$crud->grid->addColumn('Expander','point_options');

		
	}

	function page_rulegroups(){
		$crud = $this->add('xepan\hr\CRUD');
		$crud->setModel('xepan\base\RuleGroup');

	}

	function page_point_options(){
		$rules_id = $this->app->stickyGET('rules_id');
		$rpo= $this->add('xepan\base\Model_RulesOption');
		$rpo->addCondition('rule_id',$rules_id);

		$crud = $this->add('xepan\hr\CRUD',['pass_acl'=>true]);
		$crud->setModel($rpo,['name','description','score_per_qty']);


	}
}