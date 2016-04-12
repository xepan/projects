<?php

namespace xepan\projects;

class Model_Formatted_Task extends \xepan\projects\Model_Task{
	function init(){
		parent::init();

		$this->addExpression('color')->set(function($m){
			return $m->dsql()->expr(
					"IF([0]>=90,'danger',
						if([0]>=75,'warning',
						if([0]>=50,'info',
						if([0]>=25,'success','danger'	
						))))",

					  [
						$m->getElement('priority')
					  ]

					);
		});

	}
}