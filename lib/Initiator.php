<?php

namespace xepan\projects;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_projects';

	function setup_admin(){
		
		$this->routePages('xepan_projects');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('../vendor/xepan/projects/');


		if($this->app->auth->isLoggedIn()){ 

			if($this->app->inConfigurationMode)
	            $this->populateConfigurationMenus();
	        else
	            $this->populateApplicationMenus();

			$reminder = $this->app->page_top_right_button_set->addButton('Reminder')->addClass('btn btn-primary');
			$reminder->js('click')->univ()->frameURL("REMINDERS",$this->api->url('xepan_projects_reminder'));

			$this->app->addHook('post-init',function($app)use($reminder){
					try{
						$this->app->profiler->mark('Adding_mini_task');
						$app->layout->add('xepan\projects\View_MiniTask',null,'task_status');
						$this->app->profiler->mark('Mini_task_added');
					}catch(\Exception_StopInit $e){

					}
					$this->app->page_top_right_button_set->add('Order')->move($reminder,'last')->now();
			});
			
		}

		$search_project = $this->add('xepan\projects\Model_Project');
		$this->app->addHook('quick_searched',[$search_project,'quickSearch']);
		$this->app->addHook('activity_report',[$search_project,'activityReport']);
		$this->app->addHook('logout_page',[$this,'logoutPageManage']);
		$this->app->addHook('widget_collection',[$this,'exportWidgets']);
        $this->app->addHook('entity_collection',[$this,'exportEntities']);
        $this->app->addHook('collect_shortcuts',[$this,'collect_shortcuts']);

        $task = $this->add('xepan\projects\Model_Task');
        $task->addCondition('type','Followups');

		$this->app->addHook('communication_rendered',[$task,'addFollowups']);
		$this->app->user_menu->addItem(['My Timesheet','icon'=>'fa fa-clock-o'],'xepan_projects_todaytimesheet');
		
		// $this->app->report_menu->addItem(['Employee Communication Activity Report','icon'=>'fa fa-users'],$this->app->url('xepan_projects_report_employee'));
		// $this->app->report_menu->addItem(['Employee Task Report','icon'=>'fa fa-users'],$this->app->url('xepan_projects_report_task'));

		return $this;

	}

	function populateConfigurationMenus(){
		$m = $this->app->top_menu->addMenu('Projects & Tasks');
        $m->addItem(['Force Sitting Ideal Info','icon'=>'fa fa-cog'],$this->app->url('xepan_projects_configuration_task'));
        $m->addItem(['Task Reminder Layout','icon'=>'fa fa-cog'],$this->app->url('xepan_projects_configuration_layouts'));
        $m->addItem(['Task Subtype','icon'=>'fa fa-cog'],$this->app->url('xepan_projects_configuration_tasksubtype'));
	}

	function populateApplicationMenus(){
		if(!$this->app->getConfig('hidden_xepan_projects',false)){

				// $m = $this->app->top_menu->addMenu('Projects');
				// $m->addItem(['Dashboard','icon'=>'fa fa-dashboard'],'xepan_projects_projectdashboard');
				// $m->addItem(['Project','icon'=>'fa fa-sitemap'],'xepan_projects_project');
				// $m->addItem(['Trace Employee','icon'=>' fa fa-paw'],'xepan_projects_projectlive');
				// $m->addItem(['Manage Point Rules','icon'=>' fa fa-paw'],'xepan_projects_pointsystem');
				$this->app->user_menu->addItem(['Tasks','icon'=>'fa fa-tasks'],'xepan_projects_mytasks');
				// $this->app->user_menu->addItem(['My Followups','icon'=>'fa fa-stack-exchange'],'xepan_projects_myfollowups');
				// $projects = $this->add('xepan\projects\Model_Project');
				// $m->addItem(['Configuration','icon'=>' fa fa-cog'],'xepan_projects_configuration');
				// $m->addItem(['Reports','icon'=>' fa fa-cog'],'xepan_projects_projectreport');
			}
	}

		// used for custom menu
	function getTopApplicationMenu(){
		if($this->app->getConfig('hidden_xepan_projects',false)){return [];}

		return ['Projects'=>[
					[	'name'=>'Project',
						'icon'=>'fa fa-sitemap',
						'url'=>'xepan_projects_project'
					],
					[
						'name'=>'Trace Employee',
						'icon'=>'fa fa-paw',
						'url'=>'xepan_projects_projectlive'
					],
					[
						'name'=>'Manage Point Rules',
						'icon'=>'fa fa-paw',
						'url'=>'xepan_projects_pointsystem'
					],
					[
						'name'=>'My Tasks',
						'icon'=>'fa fa-tasks',
						'url'=>'xepan_projects_mytasks',
						'skip_default'=>true
					],
					[	'name'=>'Reports',
						'icon'=>'fa fa-cog',
						'url'=>'xepan_projects_projectreport'
					]
				],
				'Reports'=>[
					[	'name'=>'Employee Communication Activity Report',
						'icon'=>'fa fa-users',
						'url'=>'xepan_projects_report_employee'
					],
					[
						'name'=>'Employee Task Report',
						'icon'=>'fa fa-users',
						'url'=>'xepan_projects_report_task'
					]
				]
			];
	}

	function getConfigTopApplicationMenu(){
		if($this->app->getConfig('hidden_xepan_projects',false)){return [];}

		return [
				'Projects_Config'=>[
					[
						'name'=>'Force Sitting Ideal Info',
						'icon'=>'fa fa-cog',
						'url'=>'xepan_projects_configuration_task'
					],
					[
						'name'=>'Task Reminder Layout',
						'icon'=>'fa fa-cog',
						'url'=>'xepan_projects_configuration_layouts'
					],
					[
						'name'=>'Task Subtype',
						'icon'=>'fa fa-cog',
						'url'=>'xepan_projects_configuration_tasksubtype'
					]
			    ]
			];

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
        $array['followup_status'] = ['caption'=>'Status','type'=>'DropDown','values'=>['Pending'=>'Pending','Submitted'=>'Submitted','Completed'=>'Completed','Assigned'=>'Assigned','Inprogress'=>'Inprogress']];
        $array['EMPLOYEE_REMINDER_RELATED_EMAIL'] = ['caption'=>'EMPLOYEE_REMINDER_RELATED_EMAIL','type'=>'DropDown','model'=>'xepan\projects\Model_EMPLOYEE_REMINDER_RELATED_EMAIL'];
        $array['Employee_Running_Task_And_Timesheet'] = ['caption'=>'Employee Running Task And Timesheet','type'=>'DropDown','model'=>'xepan\projects\Model_Employee'];
    }

	function logoutPageManage($app,$logout_page){		
		$timesheet = $this->add('xepan\projects\Model_Timesheet');
		$timesheet->addCondition('employee_id',$this->app->employee->id);
		$timesheet->addCondition('endtime',null);
		$timesheet->tryLoadAny();
		
		if($timesheet->loaded())
			$logout_page->add('View')->setHtml('<b>A task is running. You can stop it or leave it running if your outing is official</b>')->addClass('label label-danger');
	}

	function collect_shortcuts($app,&$shortcuts){
		$shortcuts[]=["title"=>"New Task","keywords"=>"new quick task assign work","description"=>"Assign a new task to any one or your self","normal_access"=>"My Menu -> Tasks / New Task Button","url"=>$this->app->url('xepan/projects/mytasks',['admin_layout_cube_mytasks_virtualpage'=>'true']),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Tasks","keywords"=>"task list my pending work assigned","description"=>"Current status of your tasks","normal_access"=>"My Menu -> Tasks","url"=>$this->app->url('xepan/projects/mytasks'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Projects","keywords"=>"projects running works","description"=>"Companies Projects","normal_access"=>"Projects -> Project","url"=>$this->app->url('xepan_projects_project'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Trace Employee","keywords"=>"trace what everyone is doing working on what","description"=>"What Everyone is working on","normal_access"=>"Projects -> Trace Employee","url"=>$this->app->url('xepan_projects_projectlive'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Alert Reminder Email Content Force Sitting Ideal","keywords"=>"alert reminder email layout content what are you doing sitting ideal","description"=>"Set Alert Email Reminder layout & Task Configuration","normal_access"=>"Projects -> Configuration","url"=>$this->app->url('xepan_projects_configuration'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Todays Followup","keywords"=>"today todo followup follow up","description"=>"Your todays follow-ups","normal_access"=>"My Menu -> My Followups","url"=>$this->app->url('xepan_projects_myfollowups'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"My OverDue Followup","keywords"=>"all overdue pending previous missed followup follow up","description"=>"Your overdue follow-ups","normal_access"=>"My Menu -> My Followups","url"=>$this->app->url('xepan_projects_myfollowups',['show_overdue'=>1]),'mode'=>'frame'];
		// $shortcuts[]=["title"=>"HR Posts 1","keywords"=>"My Followup","description"=>"Bla bla bla","normal_access"=>"Commerce -> Configuration, Sidebar -> Payement Gateway","url"=>$this->app->url('xepan_hr_post',['status'=>'Active']),'mode'=>'frame'];
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