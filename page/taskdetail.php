<?php 

namespace xepan\projects;

class page_taskdetail extends \xepan\base\Page{
	function init(){
		parent::init();

		$task_id = $this->app->stickyGET('task_id')?:0;
		$project_id = $this->app->stickyGET('project_id');

		$model_task = $this->add('xepan\projects\Model_Task');
		$model_task->tryLoadBy('id',$task_id);

		if($model_task->loaded()){
			if($model_task['type'] == 'Followup')
				$this->add('xepan\projects\View_FollowUps',['task_id'=>$task_id,'project_id'=>$project_id]);
			else	
				$this->add('xepan\projects\View_Detail',['task_id'=>$task_id,'project_id'=>$project_id]);
		}
	}
}