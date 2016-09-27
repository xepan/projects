<?php

namespace xepan\projects;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_projects';

	function setup_admin(){
		
		$this->routePages('xepan_projects');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('../vendor/xepan/projects/');

		if($this->app->auth->isLoggedIn()){ 

			$reminder = $this->app->layout->add('View',null,'page_top_right',['view\reminder']);
			$reminder->js('click')->univ()->frameURL("REMINDERS",$this->api->url('xepan_projects_reminder'));


			$this->app->layout->add('xepan\projects\View_MiniTask',null,'task_status');
			
			$m = $this->app->top_menu->addMenu('Projects');
			$m->addItem(['Dashboard','icon'=>'fa fa-dashboard'],'xepan_projects_projectdashboard');
			$m->addItem(['Project','icon'=>'fa fa-sitemap'],'xepan_projects_project');
			$m->addItem(['Trace Employee','icon'=>' fa fa-paw'],'xepan_projects_projectlive');
			$m->addItem(['My Tasks','icon'=>'fa fa-tasks'],'xepan_projects_mytasks');
			$projects = $this->add('xepan\projects\Model_Project');
			// foreach ($projects as $project) {
			// 	$project_name = $project['name'];
			// 	$project_id = $project['id'];

			// 	$task_count = $project->ref('xepan\projects\Task')->addCondition('employee_id',$this->app->employee->id)->addCondition('status','Pending')->count()->getOne();
				
			// 	$m->addItem([$project_name,'icon'=>' fa fa-tasks'],$this->app->url('xepan_projects_projectdetail',['project_id'=>$project_id]),['project_id']);
			// }
			$m->addItem(['Configuration','icon'=>' fa fa-cog'],'xepan_projects_layout');
			$m->addItem(['Reports','icon'=>' fa fa-cog'],'xepan_projects_report');
		}

		$search_project = $this->add('xepan\projects\Model_Project');
		$this->app->addHook('quick_searched',[$search_project,'quickSearch']);
		return $this;

	}
	function setup_frontend(){
		$this->routePages('xepan_projects');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('./vendor/xepan/projects/');

		$this->app->addHook('cron_executor',function($app){
			$now = \DateTime::createFromFormat('Y-m-d H:i:s', $this->app->now);
			$job1 = new \Cron\Job\ShellJob();
			$job1->setSchedule(new \Cron\Schedule\CrontabSchedule('*/1 * * * *'));
			if(!$job1->getSchedule() || $job1->getSchedule()->valid($now)){
				echo " Executing Task Cron For Reminder And Recuring Tasks<br/>";
				// try{
					$recurring_task = $this->add('xepan\projects\Model_Task');
					$recurring_task->recurring();				
					$task = $this->add('xepan\projects\Model_Task');
					$task->reminder();
				// }catch(\Exception $e){}		
			}
		});


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