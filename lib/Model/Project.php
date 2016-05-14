<?php

namespace xepan\projects;

class Model_Project extends \xepan\base\Model_Table
{	
	public $table = "project";
	
	public $status=[
		'Running',
		'Onhold',
		'Completed'
	];
	
	public $actions=[
		'Running'=>['view','edit','delete','onhold','completed'],
		'Onhold'=>['view','edit','delete','running','completed'],
		'Completed'=>['view','edit','delete','running']
	];

	function init()
	{
		parent::init();
		
		$this->hasOne('xepan\hr\Employee','created_by_id');
		$this->addField('name')->sortable(true);
		$this->addField('description');	
		$this->addField('status')->defaultValue('Draft');
		$this->addField('type');

		$this->addCondition('type','project');


		$this->hasMany('xepan\projects\Task','project_id');
		$this->hasMany('xepan\projects\Team_Project_Association','project_id');

		$this->addHook('beforeDelete',[$this,'checkExistingTask']);
		$this->addHook('beforeDelete',[$this,'checkExistingTeamProjectAssociation']);
	}

	function getAssociatedTeam(){
		$associated_team = $this->ref('xepan\projects\Team_Project_Association')
								->_dsql()->del('fields')->field('employee_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_team)),false);
	}

	function removeAssociateTeam(){
		$this->ref('xepan\projects\Team_Project_Association')->deleteAll();
	}

	function checkExistingTask(){
		// $m->ref('xepan\projects\Task')->each(function($m){$m->delete();});
		$task=$this->add('xepan\projects\Model_Task');
		$task->addCondition('project_id',$this->id);
		$task->tryLoadAny();

		if($task->count()->getOne()){
			throw new \Exception("Can'not Delete Project, First delete associated tasks", 1);
			
		}
	}

	function checkExistingTeamProjectAssociation(){
		$team_asso_count=$this->ref('xepan\projects\Team_Project_Association')->each(function($m){$m->delete();});
	}

	function beforedelete(){
		$task=$this->add('xepan\projects\Model_Task');
		$task->addCondition('project_id',$this->id);
		$task->tryLoadAny();

		if($task->count()->getOne()){
			throw new \Exception("Can'not Delete Project, First delete associated tasks", 1);
			
		}
	}

	function quickSearch($app,$search_string,$view){
		$this->addExpression('Relevance')->set('MATCH(name, description, type) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		$this->addCondition('Relevance','>',0);
 		$this->setOrder('Relevance','Desc');
 		if($this->count()->getOne()){
 			$pc = $view->add('Completelister',null,null,['view/quicksearch-project-grid']);
 			$pc->setModel($this);
    		$pc->addHook('formatRow',function($g){
    			$g->current_row_html['url'] = $this->app->url('xepan_projects_projectdetail',['project_id'=> $g->model->id]);	
     		});	
		}

		$task = $this->add('xepan\projects\Model_Task');
		$task->addExpression('Relevance')->set('MATCH(task_name, description, status, type) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		$task->addCondition('Relevance','>',0);
 		$task->setOrder('Relevance','Desc');
 		if($task->count()->getOne()){
 			$tc = $view->add('Completelister',null,null,['view/quicksearch-project-grid']);
 			$tc->setModel($task);
    		$tc->addHook('formatRow',function($g){
    			$g->current_row_html['url'] = $this->app->url('xepan_projects_projectdetail',['project_id'=> $g->model['project_id']]);	
     		});	
		}


	}	
}