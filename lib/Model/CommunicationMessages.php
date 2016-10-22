<?php

namespace xepan\projects;

class Model_CommunicationMessages extends \xepan\base\Model_Table
{	
	function init()
	{
		parent::init();
	}
/**
Project Application
*/
	//Model_Comment
	function afterInsert(){
		$this->app->employee->
		addActivity("Comment On Task: '".$task_name."' Comment By'".$this->app->employee['name']."'",null, $this['employee_id'] /*Related Contact ID*/,null,null,null)->
		notifyTo([$this['employee_id'],$task_created_by]," Comment : '".$this['comment']."' :: Commented by '".$this->app->employee['name']."' :: On Task '".$task_name."' ");
	}

	// Model Task
	function notifyAssignement(){
			
			$this->app->employee
	            ->addActivity("Task '".$this['task_name']."' assigned to '". $emp_name ."'",null, $this['created_by_id'] /*Related Contact ID*/,null,null,null)
	            ->notifyTo([$this['assign_to_id']],$assigntask_notify_msg); 
	}

	function submit(){
		
		if($this['assign_to_id']){
			$this->app->employee
		              ->addActivity("Task '".$this['task_name']."' submitted by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		              ->notifyTo([$this['created_by_id']],"Task : '" . $this['task_name'] ."' Submitted by '".$this->app->employee['name']."'");
		}
		
	 	$this->app->page_action_result = $this->app->js()->_selector('.xepan-mini-task')->trigger('reload');
	}

	function receive(){
		// throw new \Exception($this->id." = ".$this['status']);
		
		
		if($this['assign_to_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' received by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id']],"Task : '".$this['task_name']."' Received by '".$this->app->employee['name']."'");
		}	

		return true;
	}

	function reject(){

		if($this['assign_to_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' rejected by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id']],"Task :'".$this['task_name']."' Rejected by '".$this->app->employee['name']."'");
		}

		return true;	
	}

	function mark_complete(){		
		if($this['assign_to_id'] == $this['created_by_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' completed by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null);
		}else{
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' completed by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id'],$this['assign_to_id']],"Task : ".$this['task_name']."' marked Complete by '".$this->app->employee['name']."'");
		}

	 	$this->app->page_action_result = $this->app->js()->_selector('.xepan-mini-task')->trigger('reload');
	}

	function page_reopen($p){
		
		if($form->isSubmitted()){
			$this->reopen($form['comment']);
			if($this['assign_to_id']){
				$this->app->employee
			            ->addActivity("Task '".$this['task_name']."' reopen by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
			            ->notifyTo([$this['assign_to_id']],"Task : '".$this['task_name']."' ReOpenned by '".$this->app->employee['name']."' Due To Reason : '".$form['comment']."'");
			}
			return $p->js()->univ()->closeDialog();
		}
	}

	//model_Project
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

/**
HR Application
*/
/**
Commerce Application
*/
/**
Marketing Application
*/
/**
Account Application
*/
/**
Production Application
*/
/**
CRM Application
*/
/**
CMS Application
*/
/**
BLOG Application
*/
}
