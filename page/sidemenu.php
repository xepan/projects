<?php
namespace xepan\projects;
class page_sidemenu extends \xepan\base\Page{
	function init(){
		parent::init();

		$projects = $this->add('xepan\projects\Model_Project');
		$project_count = $projects->count()->getOne();

		$this->app->side_menu->addItem(['Dashboard','icon'=>' fa fa-dashboard','badge'=>['10','swatch'=>' label label-primary label-circle pull-right']],'xepan_projects_projectdashboard');

		$this->app->side_menu->addItem(['Projects','icon'=>' fa fa-sitemap','badge'=>[$project_count,'swatch'=>' label label-primary label-circle pull-right']],'xepan_projects_project');
		
		foreach ($projects as $project) {
			$project_name = $project['name'];
			$project_id = $project['id'];

			$task_count = $project->ref('xepan\projects\Task')->addCondition('employee_id',$this->app->employee_id)->addCondition('status','Pending')->count()->getOne();
			
			$this->app->side_menu->addItem([$project_name,'icon'=>' fa fa-tasks','badge'=>[$task_count,'swatch'=>' label label-primary label-circle pull-right']],$this->app->url('xepan_projects_projectdetail',['project_id'=>$project_id]));
		}
	}
}