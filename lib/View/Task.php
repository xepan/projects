<?php

namespace xepan\projects;

class View_Task extends \View{
	function init(){
		parent::init();
		$self = $this;
		$self_url=$this->app->url(null,['cut_object'=>$this->name]);

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


		$vp2 = $this->add('VirtualPage');
		$vp2->set(function($p)use($self,$self_url){
			
		});

		$this->on('click','#assigntask',function($js,$data)use($vp){
			return $js->univ()->dialogURL("ASSIGN TASK TO EMPLOYEE",$this->api->url($vp->getURL(),['task_id'=>$data['task_id']]));
		});

		$this->on('click','#addfollowers',function($js,$data)use($vp2){
			return $js->univ()->dialogURL("ADD PEOPLE TO FOLLOW THIS TASK",$this->api->url($vp2->getURL(),['task_id'=>$data['task_id']]));
		});
	}

	function defaultTemplate(){
		return['view\taskdetail'];
	}
}