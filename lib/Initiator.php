<?php

namespace xepan\projects;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_projects';

	function setup_admin(){
		
		$this->routePages('xepan_projects');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('../vendor/xepan/projects/');

		if($this->app->auth->isLoggedIn()){
			$m = $this->app->top_menu->addMenu('Projects');
			$m->addItem(['Dashboard','icon'=>'fa fa-dashboard'],'xepan_projects_projectdashboard');
			$m->addItem(['Trace Employee','icon'=>' fa fa-paw'],'xepan_projects_projectlive');
			$projects = $this->add('xepan\projects\Model_Project');
			foreach ($projects as $project) {
				$project_name = $project['name'];
				$project_id = $project['id'];

				$task_count = $project->ref('xepan\projects\Task')->addCondition('employee_id',$this->app->employee->id)->addCondition('status','Pending')->count()->getOne();
				
				$m->addItem([$project_name,'icon'=>' fa fa-tasks'],$this->app->url('xepan_projects_projectdetail',['project_id'=>$project_id]));
			}
		}
		return $this;

	}
	function setup_frontend(){
		$this->routePages('xepan_projects');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('./vendor/xepan/projects/');	
		return $this;	
	}

	function resetDB(){
		// Clear DB
		if(!isset($this->app->old_epan)) $this->app->old_epan = $this->app->epan;
        if(!isset($this->app->new_epan)) $this->app->new_epan = $this->app->epan;
        
        $this->app->epan=$this->app->old_epan;
        $truncate_models = ['Follower_Task_Association','Comment','Timesheet','Task_Attachment','Task','Project'];
        foreach ($truncate_models as $t) {
            $m=$this->add('xepan\projects\Model_'.$t);
            foreach ($m as $mt) {
                $mt->delete();
            }
        }
        
        $this->app->epan=$this->app->new_epan;
	}
}