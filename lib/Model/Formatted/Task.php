<?php

namespace xepan\projects;

class Model_Formatted_Task extends \xepan\projects\Model_Task{
	function init(){
		parent::init();

		$this->addExpression('color')->set(function($m){
			return $m->dsql()->expr(
					"IF([0]>=90,'danger',
						if([0]>=75,'warning',
						if([0]>=50,'primary',
						if([0]>=25,'success','danger'	
						))))",

					  [
						$m->getElement('priority')
					  ]

					);
		});

		$this->addExpression('is_started')->set(function($m,$q){

			return $m->refSQL('xepan\projects\Timesheet')
							->count();
		});

		$this->addExpression('is_running')->set(function($m,$q){							
			return $m->refSQL('xepan\projects\Timesheet')
							->addCondition('endtime',null)
							->count()
							;
		});

		// $this->debug();

		$this->addExpression('total_duration')->set(function($m,$q){
			$time_sheet = $this->add('xepan\projects\Model_Timesheet',['table_alias'=>'total_duration']);
			$time_sheet->addCondition('task_id',$q->getField('id'));
			return $time_sheet->dsql()->del('fields')->field($q->expr('sec_to_time(SUM([0]))',[$time_sheet->getElement('duration')]));
		});

		// $this->addExpression('comment_count')->set(function($m,$q){
		// 	return $m->refSQL('xepan\projects\Comment')->count();
		// });


		$this->addExpression('attachment_count')->set(function($m,$q){
			return $m->refSQL('xepan\projects\Task_Attachment')->count();
		});
	}
}