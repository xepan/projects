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
		'Completed'=>['view','edit','delete']
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
            ->addActivity("Project '".$this['name']."' in progress and its status being 'Running' ", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_projects_projectdetail&project_id=".$this->id."")
            ->notifyWhoCan('onhold,complete','Running',$this);
		$this->save();
	}

	function onhold(){
		$this['status']='Onhold';
		$this->app->employee
            ->addActivity("Project '".$this['name']."' kept on hold ", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_projects_projectdetail&project_id=".$this->id."")
            ->notifyWhoCan('complete,run','Onhold',$this);
		$this->save();
	}

	function complete(){
		$this['status']='Completed';
		$this['actual_completion_date'] = $this->app->today;
		$this->app->employee
            ->addActivity("Project '".$this['name']."' has been completed on date of '".$this['actual_completion_date']."' ", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_projects_projectdetail&project_id=".$this->id."")
            ->notifyWhoCan(' ','Completed',$this);
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

	function activityReport($app,$report_view,$emp,$start_date,$end_date){		
		$employee = $this->add('xepan\hr\Model_Employee')->load($emp);
							  					  
		$task = $this->add('xepan\projects\Model_Task');
		$task->addCondition('type','Task');
		$task->addCondition('created_at','>=',$start_date);
		$task->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$task->addCondition('assign_to_id',$emp);
		$task_count = $task->count()->getOne();
		
		$result_array[] = [
 					'assign_to'=>$employee['name'],
 					'from_date'=>$start_date,
 					'to_date'=>$end_date,
 					'type'=> 'Task',
 					'count'=>$task_count,
 				];
 		
		$followup = $this->add('xepan\projects\Model_Task');
		$followup->addCondition('type','Followup');
		$followup->addCondition('created_at','>=',$start_date);
		$followup->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$followup->addCondition('assign_to_id',$emp);
		$followup_count = $followup->count()->getOne();

		$result_array[] = [
				'assign_to'=>$employee['name'],
				'from_date'=>$start_date,
				'to_date'=>$end_date,
				'type'=> 'Followup',
				'count'=>$followup_count,
			];

		$reminder = $this->add('xepan\projects\Model_Task');
		$reminder->addCondition('type','Reminder');
		$reminder->addCondition('created_at','>=',$start_date);
		$reminder->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$reminder->addCondition('assign_to_id',$emp);
		$reminder_count = $reminder->count()->getOne();

		$result_array[] = [
				'assign_to'=>$employee['name'],
				'from_date'=>$start_date,
				'to_date'=>$end_date,
				'type'=> 'Reminder',
				'count'=>$reminder_count,
			];

		$cl = $report_view->add('CompleteLister',null,null,['view\projectactivityreport']);
		$cl->setSource($result_array);
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