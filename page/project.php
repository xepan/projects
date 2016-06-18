<?php

namespace xepan\projects;

class page_project extends \xepan\projects\page_sidemenu{
	public $title = "Add/Edit Project";
	function init(){
		parent::init();

		$project = $this->add('xepan\projects\Model_Formatted_Project');
		$crud=$this->add('xepan\hr\CRUD',null,null,['view\project-grid']);
		$crud->setModel($project);

		$crud->grid->addQuickSearch('name');

		// $color = array("emerald", "green","red","yellow","purple","gray");
		$color = [
					0=>"emerald", 
					1=>"green",
					2=>"red",
					3=>"yellow",
					4=>"purple",
					5=>"gray" 
				 ];
		$this->count = 0;		 
		$crud->grid->addHook('formatRow',function($g) use($color){
			if($this->count > 5) $this->count = 0;

			$g->current_row_html['box'] = $color[$this->count].'-box'; 	
			$g->current_row_html['bg'] = $color[$this->count].'-bg';	

			$this->count++;									
		});

		/***************************************************************************
			Virtual page for assigning TEAM
		***************************************************************************/
		$vp = $this->add('VirtualPage');
		$vp->set(function($p){

			$project_id = $this->app->stickyGET('project_id');

			$model_employee = $p->add('xepan\hr\Model_Employee');
			$model_project = $p->add('xepan\projects\Model_Project')->load($project_id);
			$model_team_project_association = $p->add('xepan\projects\Model_Team_Project_Association');

			$form = $p->add('Form');
			$team_field = $form->addField('line','team')->set(json_encode($model_project->getAssociatedTeam()));

			// Selectable for "Team" 

			$team_grid = $p->add('xepan\base\Grid');
			$team_grid->setModel($model_employee,['name']);
			$team_grid->addSelectable($team_field);

			if($form->isSubmitted()){
				$model_project->removeAssociateTeam();
				
				$selected_team = array();
			 	$selected_team = json_decode($form['team'],true);
			 	
				foreach ($selected_team as $team) {
					$model_team_project_association->addCondition('project_id',$_GET['project_id']);
					$model_team_project_association['employee_id'] = $team;
					$model_team_project_association->saveAndUnload();
				}
				$form->js()->univ()->closeDialog()->execute(); 
			}
		});

		/***************************************************************************
			Js for assigning TEAM
		***************************************************************************/

		$this->on('click','#addteam',function($js,$data)use($vp){
			return $js->univ()->dialogURL("ADD Team",$this->api->url($vp->getURL(),['project_id'=>$data['project_id']]));
		});
		
	}

	function render(){

		$this->app->jui->addStaticInclude('pace.min');
		$this->app->jui->addStaticInclude('jquery.easypiechart.min');
		parent::render();
	}	
}