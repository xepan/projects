<?php

namespace xepan\projects;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_projects';

	function setup_admin(){
		
		$this->routePages('xepan_projects');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('../vendor/xepan/projects/');

		if($this->app->auth->isLoggedIn()){

			$mini_task_view = $this->app->layout->add('View',null,'task_status',['view\minitask'])->set('hi');
			
			$model_timesheet = $this->add('xepan\projects\Model_Timesheet');
			$model_timesheet->addCondition('employee_id',$this->app->employee->id);
			$model_timesheet->setOrder('starttime','desc');
			$model_timesheet->tryLoadAny();

			$model_task = $this->add('xepan\projects\Model_Formatted_Task');
			$model_task->tryload($model_timesheet['task_id']);				
			$mini_task_view->setModel($model_task);

			$mini_task_view->on('click','.current_task_btn',function($js,$data)use($mini_task_view){
			
			$model_timesheet = $this->add('xepan\projects\Model_Timesheet');

			$model_timesheet->addCondition('employee_id',$this->app->employee->id);
			$model_timesheet->setOrder('starttime','desc');
			$model_timesheet->tryLoadAny();

			if($model_timesheet->loaded()){
				if(!$model_timesheet['endtime']){
					$model_timesheet['endtime'] = $this->app->now;
					$model_timesheet->save();
				}
			}

			if($data['action']=='start'){

				$model_timesheet1 = $this->add('xepan\projects\Model_Timesheet');
					
				$model_timesheet1['task_id'] = $data['id'];
				$model_timesheet1['employee_id'] = $this->app->employee->id;
				$model_timesheet1['starttime'] = $this->app->now;
				$model_timesheet1->save();

				return [
						$this->js()->_selector('.current_task_btn')->removeClass('fa-stop')->addClass('fa-play'),
						$this->js()->_selector('.dd3-content')->removeClass('alert alert-info'),
						$js->removeClass('fa-play')->addClass('fa-stop')->data('action','stop'),
						$this->js()->_selector('.dd3-content[data-id='.$data['id'].']')->addClass('alert alert-info'),
					];
			}

			return $js->removeClass('fa-stop')->addClass('fa-play')->data('action','start');	


		});



			$vp = $this->add('VirtualPage');
			$vp->set(function($p){
				$p->add('xepan\projects\View_InstantTaskFeed');		
			});

			$mini_task_view->js('click',$this->app->js()->univ()->dialogURL("INSTANT TASK FEED",$this->api->url($vp->getURL())))->_selector('.instant-task-feed');

			$m = $this->app->top_menu->addMenu('Projects');
			$m->addItem(['Dashboard','icon'=>'fa fa-dashboard'],'xepan_projects_projectdashboard');
			$m->addItem(['Project','icon'=>'fa fa-sitemap'],'xepan_projects_project');
			$m->addItem(['Trace Employee','icon'=>' fa fa-paw'],'xepan_projects_projectlive');
			$projects = $this->add('xepan\projects\Model_Project');
			foreach ($projects as $project) {
				$project_name = $project['name'];
				$project_id = $project['id'];

				$task_count = $project->ref('xepan\projects\Task')->addCondition('employee_id',$this->app->employee->id)->addCondition('status','Pending')->count()->getOne();
				
				$m->addItem([$project_name,'icon'=>' fa fa-tasks'],$this->app->url('xepan_projects_projectdetail',['project_id'=>$project_id]));
			}
		}

		$search_project = $this->add('xepan\projects\Model_Project');
		$this->app->addHook('quick_searched',[$search_project,'quickSearch']);
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