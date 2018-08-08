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
		'Running'=>['view','edit','delete','onhold','complete','delete_with_related_document'],
		'Onhold'=>['view','edit','delete','run','complete','delete_with_related_document'],
		'Completed'=>['view','edit','delete','delete_with_related_document']
	];

	function init()
	{
		parent::init();
		
		$this->hasOne('xepan\hr\Employee','created_by_id')->defaultValue($this->app->employee->id);
		$this->hasOne('xepan\base\Branch','branch_id')->defaultValue(@$this->app->branch->id);
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
							  					  
		$task_status = $this->add('xepan\projects\Model_Widget_TaskStatus',['start_date'=>$start_date,'end_date'=>$end_date]);
		$task_status->addCondition('id',$emp);
		
		// $cl = $report_view->add('CompleteLister',null,null,['view\projectactivityreport']);
		// $cl->setSource($result_array);

		// $c->current_row_html['page_url'] = (string) $this->app->url('xepan_projects_activity_report',['type'=>$c->model['type']]);
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


	function page_delete_with_related_document($page){

		$task = $this->add('xepan\projects\Model_Task',['force_delete'=>true]);
		$task->addCondition('project_id',$this->id);
		$total_task = $task->count()->getOne();
		$total_team_association = $this->ref('xepan\projects\Team_Project_Association')->count()->getOne();

		$form = $page->add('Form');
		$form->add('View')->set("Total Task to be delete: ".$total_task);
		$form->add('View')->set("Total Team Association to be delete: ".$total_team_association);

		$form->addSubmit('are you sure, you want to delete')->addClass('btn btn-primary');
		if($form->isSubmitted()){
			$name = $this['name'];
			$this->delete_with_related_document();

			$this->app->employee
            	->addActivity("Project '".$name."' Forcefully deleted, that include total task: '".$total_task." and team association: ".$total_team_association, null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,null);
            
			return $this->api->js(null,$form->js()->univ()->closeDialog())->univ()->successMessage('Record Deleted Successfully');
		}
	}

	function delete_with_related_document(){

		$task = $this->add('xepan\projects\Model_Task',['force_delete'=>true]);
		$task->addCondition('project_id',$this->id);
		$task->each(function($m){
			$m->delete();
		});

		$this->ref('xepan\projects\Team_Project_Association')
							->each(function($m){
								$m->delete();
							});
		$this->delete();
	}

}