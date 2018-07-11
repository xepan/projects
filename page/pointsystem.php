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

		$groups = $this->add('xepan\base\Model_RuleGroup');

		$layout_array = [];
		foreach ($groups as $g) {
			$layout_array[$g->id.'~'] = $g['name'].'~c'.($g->id).'~12';
		}


		$v = $this->add('View')->addClass('temp');
		$v->js('reload')->reload();
		$v->add('xepan\base\Controller_FLC')
		->showLables(true)
		->makePanelsCoppalsible(true)
		->layout($layout_array);


		foreach ($groups as $g) {
			$rules_m = $this->add('xepan\base\Model_Rules');
			$rules_m->getElement('created_by_id')->defaultValue($this->app->employee->id);
			$rules_m->addCondition('rulegroup_id',$g->id);
			$crud = $v->add('xepan\hr\CRUD',['grid_options'=>['fixed_header'=>false]],$g->id);
			$crud->setModel($rules_m);

			$crud->noAttachment();

			$crud->grid->addColumn('Expander','point_options');
		}
		
	}

	function page_rulegroups(){
		$model=$this->add('xepan\base\Model_RuleGroup');
		$model->getElement('created_by_id')->defaultValue($this->app->employee->id);
		$crud = $this->add('xepan\hr\CRUD');
		$crud->setModel($model);

	}

	function page_point_options(){
		$rules_id = $this->app->stickyGET('rules_id');
		$rpo= $this->add('xepan\base\Model_RulesOption');
		$rpo->addCondition('rule_id',$rules_id);

		$crud = $this->add('xepan\hr\CRUD',['pass_acl'=>true]);
		$crud->setModel($rpo,['name','description','score_per_qty']);
		$crud->grid->addFormatter('name','wrap');

	}
}