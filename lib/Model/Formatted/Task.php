<?php

namespace xepan\projects;

class Model_Formatted_Task extends \xepan\projects\Model_Task{
	function init(){
		parent::init();

		$this->addExpression('color')->set(function($m){
			return $m->dsql()->expr(
					"IF([0]='Critical','danger',
						if([0]='High','warning',
						if([0]='Medium','info',
						if([0]='Low','success','danger'	
						))))",

					  [
						$m->getElement('priority')
					  ]

					);
		});

	}
}