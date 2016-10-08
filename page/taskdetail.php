<?php 

namespace xepan\projects;

class page_taskdetail extends \xepan\base\Page{
	function init(){
		parent::init();

		$task_id = $this->app->stickyGET('task_id')?:0;
		$project_id = $this->app->stickyGET('project_id');

		$this->add('xepan\projects\View_Detail',['task_id'=>$task_id,'project_id'=>$project_id]);
	}
}