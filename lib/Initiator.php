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
						$this->app->profiler->mark('Adding_mini_task');
						$app->layout->add('xepan\projects\View_MiniTask',null,'task_status');
						$this->app->profiler->mark('Mini_task_added');
					}catch(\Exception_StopInit $e){

					}
			});

			if(!$this->app->getConfig('hidden_xepan_projects',false)){

				$m = $this->app->top_menu->addMenu('Projects');
				// $m->addItem(['Dashboard','icon'=>'fa fa-dashboard'],'xepan_projects_projectdashboard');
				$m->addItem(['Project','icon'=>'fa fa-sitemap'],'xepan_projects_project');
				$m->addItem(['Trace Employee','icon'=>' fa fa-paw'],'xepan_projects_projectlive');
				$this->app->user_menu->addItem(['Tasks','icon'=>'fa fa-tasks'],'xepan_projects_mytasks');
				$this->app->user_menu->addItem(['My Followups','icon'=>'fa fa-stack-exchange'],'xepan_projects_myfollowups');
				$projects = $this->add('xepan\projects\Model_Project');
				$m->addItem(['Configuration','icon'=>' fa fa-cog'],'xepan_projects_layout');
				$m->addItem(['Reports','icon'=>' fa fa-cog'],'xepan_projects_projectreport');
			}
			
		}

		$search_project = $this->add('xepan\projects\Model_Project');
		$this->app->addHook('quick_searched',[$search_project,'quickSearch']);
		$this->app->addHook('activity_report',[$search_project,'activityReport']);
		$this->app->addHook('logout_page',[$this,'logoutPageManage']);
		$this->app->addHook('widget_collection',[$this,'exportWidgets']);
        $this->app->addHook('entity_collection',[$this,'exportEntities']);

        $task = $this->add('xepan\projects\Model_Task');
        $task->addCondition('type','Followups');

		$this->app->addHook('communication_rendered',[$task,'addFollowups']);
		$this->app->user_menu->addItem(['My Timesheet','icon'=>'fa fa-clock-o'],'xepan_projects_todaytimesheet');
		return $this;

	}

	function exportWidgets($app,&$array){
        $array[] = ['xepan\projects\Widget_AccountableSystemUse','level'=>'Global','title'=>'Staff Accountable System Use'];
        $array[] = ['xepan\projects\Widget_EmployeeTaskStatus','level'=>'Global','title'=>'Employee Task Status'];
        $array[] = ['xepan\projects\Widget_EmployeeTimesheet','level'=>'Global','title'=>'Employee Timesheet'];
        $array[] = ['xepan\projects\Widget_ProjectHourConsumption','level'=>'Global','title'=>'Project Hour Consumption'];
        $array[] = ['xepan\projects\Widget_GlobalFollowUps','level'=>'Global','title'=>'Company Followups'];
   		$array[] = ['xepan\projects\Widget_GlobalTaskPerformance','level'=>'Global','title'=>'Company Performance'];
   		$array[] = ['xepan\projects\Widget_TaskStatus','level'=>'Global','title'=>'Task Status Chart'];
   		$array[] = ['xepan\projects\Widget_TabularTask','level'=>'Global','title'=>'Employee Task Detail'];
   		$array[] = ['xepan\projects\Widget_TodaysTaskStatus','level'=>'Global','title'=>'Todays Task Status'];
        
   		$array[] = ['xepan\projects\Widget_PostTaskPerformance','level'=>'Sibling','title'=>'Post Performance'];
        
        $array[] = ['xepan\projects\Widget_DepartmentFollowUps','level'=>'Department','title'=>'Departmental Followups'];
   		$array[] = ['xepan\projects\Widget_DepartmentTaskPerformance','level'=>'Department','title'=>'Department Performance'];
   		$array[] = ['xepan\projects\Widget_DepartmentAccountableSystemUse','level'=>'Department','title'=>'Department Accountable System Use'];
        
   		$array[] = ['xepan\projects\Widget_MyTaskPerformance','level'=>'Individual','title'=>'My Performance'];
   		$array[] = ['xepan\projects\Widget_HotTasks','level'=>'Individual','title'=>'Tasks Near Deadline'];
   		$array[] = ['xepan\projects\Widget_OverdueTasks','level'=>'Individual','title'=>'Overdue Tasks'];
   		$array[] = ['xepan\projects\Widget_TaskToReceive','level'=>'Individual','title'=>'Tasks Waiting To Be Received'];
   		$array[] = ['xepan\projects\Widget_MyTaskStatus','level'=>'Individual','title'=>'My Task Status'];
        $array[] = ['xepan\projects\Widget_MyAccountableSystemUse','level'=>'Individual','title'=>'My Accountable System Use'];
        $array[] = ['xepan\projects\Widget_MyTask','level'=>'Individual','title'=>'My Tasks'];
        $array[] = ['xepan\projects\Widget_MyAssignedTask','level'=>'Individual','title'=>'My Assigned Tasks'];
        $array[] = ['xepan\projects\Widget_SubmittedTask','level'=>'Individual','title'=>'Submitted Tasks'];
        $array[] = ['xepan\projects\Widget_CurrentTask','level'=>'Individual','title'=>'Current Tasks'];
        $array[] = ['xepan\projects\Widget_FollowUps','level'=>'Individual','title'=>'My Followups'];
   	 }

    function exportEntities($app,&$array){
        $array['project'] = ['caption'=>'Project','type'=>'DropDown','model'=>'xepan\projects\Model_Project'];
        $array['Task'] = ['caption'=>'Task','type'=>'DropDown','model'=>'xepan\projects\Model_Task'];
        $array['followup_status'] = ['caption'=>'Type','type'=>'DropDown','values'=>['Pending'=>'Pending','Assigned'=>'Assigned']];
        $array['EMPLOYEE_REMINDER_RELATED_EMAIL'] = ['caption'=>'EMPLOYEE_REMINDER_RELATED_EMAIL','type'=>'DropDown','model'=>'xepan\projects\Model_EMPLOYEE_REMINDER_RELATED_EMAIL'];
    }

	function logoutPageManage($app,$logout_page){		
		$timesheet = $this->add('xepan\projects\Model_Timesheet');
		$timesheet->addCondition('employee_id',$this->app->employee->id);
		$timesheet->addCondition('endtime',null);
		$timesheet->tryLoadAny();
		
		if($timesheet->loaded())
			$logout_page->add('View')->setHtml('<b>A task is running. You can stop it or leave it running if your outing is official</b>')->addClass('label label-danger');
	}

	// function epanDashboard($layout,$page){
		
	// 	$v = $page->add('View');
	// 	$v->addClass('col-md-4');
	// 	$task_assigned_to_me = $v->add('xepan\hr\CRUD',['allow_add'=>null,'grid_class'=>'xepan\projects\View_TaskList']);	    
	//     $task_assigned_to_me->grid->addClass('task-assigned-to-me');
	//     $task_assigned_to_me->grid->template->trySet('task_view_title','My Tasks');
	//     $task_assigned_to_me->js('reload')->reload();

	// 	if(!$task_assigned_to_me->isEditing())
	// 		$task_assigned_to_me->grid->addPaginator(10);

	// 	$task_assigned_to_me_model = $page->add('xepan\projects\Model_Formatted_Task');
	//     $task_assigned_to_me_model
	//     			->addCondition('status',['Pending','Inprogress'])
	//     			->addCondition(
	//     				$task_assigned_to_me_model->dsql()->orExpr()
	//     					->where('assign_to_id',$this->app->employee->id)
	//     					->where(
 //    								$task_assigned_to_me_model->dsql()->andExpr()
 //    									->where('created_by_id',$this->app->employee->id)
 //    									->where('assign_to_id',null)
	//     							)
	//     				)
	//     			->addCondition('type','Task');

	//     $task_assigned_to_me->setModel($task_assigned_to_me_model)->setOrder('updated_at','desc');			
	
	//     $followups_view = $page->add('xepan\projects\View_MyFollowups');
	//     $followups_view->addClass('col-md-4');
	// }

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