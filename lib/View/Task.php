<?php

namespace xepan\projects;

class View_Task extends \View{
	function init(){
		parent::init();
		$self = $this;
		$self_url=$this->app->url(null,['cut_object'=>$this->name]);


		/***************************************************************************
			Virtual page for assigning task.
		***************************************************************************/	
		$vp = $this->add('VirtualPage');
		$vp->set(function($p)use($self,$self_url){
						
			$model_task = $p->add('xepan\projects\Model_Task')->load($_GET['task_id']);

			$form = $p->add('Form');
			$form->setModel($model_task,['employee_id']);
			if($form->isSubmitted()){
				$form->save();
				$form->js('null',$self->js()->reload(null,null,$self_url))->univ()->closeDialog()->execute();
			}

		});

		/***************************************************************************
			Virtual page for assigning followers
		***************************************************************************/
		$vp2 = $this->add('VirtualPage');
		$vp2->set(function($p){

			$model_task = $p->add('xepan\projects\Model_Task');
			$model_task->load($p->app->stickyGET('task_id'));

			$model_employee = $p->add('xepan\hr\Model_Employee');
			$model_follower_task_association = $p->add('xepan\projects\Model_Follower_Task_Association');
			
			$form = $p->add('Form');
			$follower_field = $form->addField('line','name')->set(json_encode($model_task->getAssociatedFollowers()));

			// Selectable for "task can have many followers" 

			$follower_grid = $p->add('xepan\base\Grid');

			$follower_grid->setModel($model_employee,['name']);
			$follower_grid->addSelectable($follower_field);

			if($form->isSubmitted()){

				$model_task->removeAssociateFollowers();
				
				$selected_followers = array();
			 	$selected_followers = json_decode($form['name'],true);

				foreach ($selected_followers as $followers) {
					$model_follower_task_association->addCondition('task_id',$_GET['task_id']);
					$model_follower_task_association['employee_id'] = $followers;
					$model_follower_task_association->saveAndUnload();
				}

				$form->js()->univ()->closeDialog()->execute(); 
			}
		});
				

		/***************************************************************************
			js click function for assign task 
		***************************************************************************/
		$this->on('click','#assigntask',function($js,$data)use($vp){
			return $js->univ()->dialogURL("ASSIGN TASK TO EMPLOYEE",$this->api->url($vp->getURL(),['task_id'=>$data['task_id']]));
		});

		/***************************************************************************
			js click function for adding followers.
		***************************************************************************/
		$this->on('click','#addfollowers',function($js,$data)use($vp2){
			return $js->univ()->dialogURL("ADD PEOPLE TO FOLLOW THIS TASK",$this->api->url($vp2->getURL(),['task_id'=>$data['task_id']]));
		});
	}

	function setModel($model,$fields=null){		
		$m = parent::setModel($model,$fields);
		$this->add('xepan\base\Controller_Avatar',['options'=>['size'=>30],'name_field'=>'employee','default_value'=>'??']);
		return $m;
	}

	function defaultTemplate(){
		return['view\taskdetail'];
	}
}