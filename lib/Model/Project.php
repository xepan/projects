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
		'Running'=>['view','edit','delete','onhold','complete'],
		'Onhold'=>['view','edit','delete','run','complete'],
		'Completed'=>['view','edit','delete','run']
	];

	function init()
	{
		parent::init();
		
		$this->hasOne('xepan\hr\Employee','created_by_id')->defaultValue($this->app->employee->id);
		$this->addField('name')->sortable(true);
		$this->addField('description');	
		$this->addField('starting_date')->type('date');	
		$this->addField('ending_date')->type('date');	
		$this->addField('actual_completion_date')->type('date');	
		$this->addField('status')->enum(['Running','Onhold','Completed'])->defaultValue('Running');
		$this->addField('type');

		$this->addCondition('type','project');


		$this->hasMany('xepan\projects\Task','project_id');
		$this->hasMany('xepan\projects\Team_Project_Association','project_id');

		$this->addHook('beforeDelete',[$this,'checkExistingTask']);
		$this->addHook('beforeDelete',[$this,'checkExistingTeamProjectAssociation']);
	}

	function run(){
		$this['status']='Running';
		$this->app->employee
            ->addActivity("Project Running", null/* Related Document ID*/, $this->id /*Related Contact ID*/)
            ->notifyWhoCan('complete,onhold','Running',$this);
		$this->save();
	}

	function onhold(){
		$this['status']='Onhold';
		$this->app->employee
            ->addActivity("Project onhold", null/* Related Document ID*/, $this->id /*Related Contact ID*/)
            ->notifyWhoCan('complete,run','Onhold',$this);
		$this->save();
	}

	function complete(){
		$this['status']='Completed';
		$this->app->employee
            ->addActivity("Lead has deactivated", null/* Related Document ID*/, $this->id /*Related Contact ID*/)
            ->notifyWhoCan('run','Completed',$this);
		$this->save();
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

	function quickSearch($app,$search_string,&$result_array,$relevency_mode){		
		$this->addExpression('Relevance')->set('MATCH(name, description, type) AGAINST ("'.$search_string.'" '.$relevency_mode.')');
		$this->addCondition('Relevance','>',0);
 		$this->setOrder('Relevance','Desc');
 		
 		if($this->count()->getOne()){
 			foreach ($this->getRows() as $data) { 				
 				$result_array[] = [
 					'image'=>null,
 					'title'=>$data['name'],
 					'relevency'=>$data['Relevance'],
 					'url'=>$this->app->url('xepan_projects_projectdetail',['status'=>$data['status'],'project_id'=>$data['id']])->getURL(),
 					'type_status'=>$data['type'].' '.'['.$data['status'].']',
 				];
 			}
		}

		$task = $this->add('xepan\projects\Model_Task');
		$task->addExpression('Relevance')->set('MATCH(task_name, description, status, type) AGAINST ("'.$search_string.'" '.$relevency_mode.')');
		$task->addCondition('Relevance','>',0);
 		$task->setOrder('Relevance','Desc');
 		
 		if($task->count()->getOne()){
 			foreach ($task->getRows() as $data) { 
 				$result_array[] = [
 					'image'=>null,
 					'title'=>$data['task_name'],
 					'relevency'=>$data['Relevance'],
 					'url'=>$this->app->url('xepan_projects_projectdetail',['status'=>$data['status'],'project_id'=>$data['id']])->getURL(),
 					'type_status'=>$data['type'].' '.'['.$data['status'].']',
 				];
 			}
		}
	}	
}