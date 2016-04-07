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
						
			$model_employee = $p->add('xepan\hr\Model_Employee');
			$model_task = $p->add('xepan\projects\Model_Task')->load($_GET['task_id']);

			$form = $p->add('Form');
			$form->addField('dropdown','name')->setModel($model_employee);

			if($form->isSubmitted()){
				$model_task['employee'] = $form['name'];
				$model_task->save();
				
				$form->js('null',$self->js()->reload(null,null,$self_url))->univ()->closeDialog()->execute();
			}

		});

		/***************************************************************************
			Virtual page for assigning followers
		***************************************************************************/
		$vp2 = $this->add('VirtualPage');
		$vp2->set(function($p){
			$model_employee = $p->add('xepan\hr\Model_Employee');
			$model_follower = $this->add('xepan\projects\Model_Follower_Task_Association');
			$form = $p->add('Form');
			$form->addField('dropdown','name')->setModel($model_employee);
			
			if($form->isSubmitted()){				
				$model_follower->addCondition('task_id',$_GET['task_id']);
				$model_follower['employee_id'] = $form['name'];
				$model_follower->save(); 
			}
		});

		/***************************************************************************
			Form for setting deadline
		***************************************************************************/
		$deadline_form = $this->add('Form',null,'deadline');
		$deadline_form->addField('DatePicker','deadline');

		if($deadline_form->isSubmitted()){
			$model_task = $this->add('xepan\projects\Model_Task')->load($_GET['task_id']);
			$model_task['deadline'] = $deadline_form['deadline'];
			$model_task->save();
		}						
		
		/***************************************************************************
			js click function for setting deadline
		***************************************************************************/
		$js_new = [
			$this->js()->_selector('#deadline')->hide(),
			$this->js()->_selector('#calendar')->show()
		];
		$this->js('click',$js_new)->_selector('.toggle');

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

	function defaultTemplate(){
		return['view\taskdetail'];
	}
}