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

			$this->app->addHook('post-init',function($app){
					try{
						$app->layout->add('xepan\projects\View_MiniTask',null,'task_status');
					}catch(\Exception_StopInit $e){

					}
			});
			
			$m = $this->app->top_menu->addMenu('Projects');
			$m->addItem(['Dashboard','icon'=>'fa fa-dashboard'],'xepan_projects_projectdashboard');
			$m->addItem(['Project','icon'=>'fa fa-sitemap'],'xepan_projects_project');
			$m->addItem(['Trace Employee','icon'=>' fa fa-paw'],'xepan_projects_projectlive');
			$this->app->user_menu->addItem(['Tasks','icon'=>'fa fa-tasks'],'xepan_projects_mytasks');
			$projects = $this->add('xepan\projects\Model_Project');
			$m->addItem(['Configuration','icon'=>' fa fa-cog'],'xepan_projects_layout');
			$m->addItem(['Reports','icon'=>' fa fa-cog'],'xepan_projects_projectreport');
		}

		$search_project = $this->add('xepan\projects\Model_Project');
		$this->app->addHook('quick_searched',[$search_project,'quickSearch']);
		$this->app->addHook('epan_dashboard_page',[$this,'epanDashboard']);
		$this->app->addHook('logout_page',[$this,'logoutPageManage']);
		$this->app->user_menu->addItem(['My Timesheet','icon'=>'fa fa-clock-o'],'xepan_projects_editabletimesheet');
		return $this;

	}

	function logoutPageManage($app,$logout_page){		
		$timesheet = $this->add('xepan\projects\Model_Timesheet');
		$timesheet->addCondition('employee_id',$this->app->employee->id);
		$timesheet->addCondition('endtime',null);
		$timesheet->tryLoadAny();
		
		if($timesheet->loaded())
			$logout_page->add('View')->setHtml('<b>A task is running. You can stop it or leave it running if your ouing is official</b>');
	}

	function epanDashboard($layout,$page){
		
		$task_assigned_to_me = $page->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList']);		
		$task_assigned_to_me->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null,'model'=>$this->model]);
		$task_assigned_to_me->grid->template->trySet('task_view_title','My Pending Task');

		if(!$task_assigned_to_me->isEditing())
			$task_assigned_to_me->grid->addPaginator(25);

		$task_assigned_to_me_model = $page->add('xepan\projects\Model_Formatted_Task');
	    $task_assigned_to_me_model
	    			->addCondition('status',['Pending','Inprogress'])
	    			->addCondition(
	    				$task_assigned_to_me_model->dsql()->orExpr()
	    					->where('assign_to_id',$this->app->employee->id)
	    					->where(
    								$task_assigned_to_me_model->dsql()->andExpr()
    									->where('created_by_id',$this->app->employee->id)
    									->where('assign_to_id',null)
	    							)
	    				);

	    $task_assigned_to_me->setModel($task_assigned_to_me_model)->setOrder('updated_at','desc');			
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
				try{
					$task = $this->add('xepan\projects\Model_Task');
					$task->reminder();
					$recurring_task = $this->add('xepan\projects\Model_Task');
					$recurring_task->recurring();				
				}catch(\Exception $e){
					throw $e;
				}		
			}
		});


		return $this;	
	}

	function resetDB(){
		// Clear DB
		// if(!isset($this->app->old_epan)) $this->app->old_epan = $this->app->epan;
  //       if(!isset($this->app->new_epan)) $this->app->new_epan = $this->app->epan;
        
  //       $this->app->epan=$this->app->old_epan;
  //       $truncate_models = ['Follower_Task_Association','Comment','Timesheet','Task_Attachment','Task','Project'];
  //       foreach ($truncate_models as $t) {
  //           $m=$this->add('xepan\projects\Model_'.$t);
  //           foreach ($m as $mt) {
  //               $mt->delete();
  //           }
  //       }
        
  //       $this->app->epan=$this->app->new_epan;
	}
}