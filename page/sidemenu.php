<?php
namespace xepan\projects;
class page_sidemenu extends \xepan\base\Page{
	function init(){
		parent::init();

		$projects = $this->add('xepan\projects\Model_Project');
		$project_count = $projects->count()->getOne();


		$this->app->side_menu->addItem(['Dashboard','icon'=>' fa fa-dashboard','badge'=>['10','swatch'=>' label label-primary label-circle pull-right']],'xepan_projects_projectdashboard');

		$this->app->side_menu->addItem(['Projects','icon'=>' fa fa-edit','badge'=>[$project_count,'swatch'=>' label label-primary label-circle pull-right']],'xepan_projects_project');

		foreach ($projects as $project) {
			$project_name = $project['name'];
			$project_id = $project['id'];

			$this->app->side_menu->addItem([$project_name],$this->app->url('xepan_projects_projectdetail',['project_id'=>$project_id]));
		}
	}
}