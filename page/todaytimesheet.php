<?php

namespace xepan\projects;

class page_todaytimesheet extends \xepan\base\Page{
	public $title = "My Timesheet";

	function page_index(){

		$this->app->stickyGET('for_date');

		$on_date = $this->app->today;
		if($_GET['for_date']) $on_date = $_GET['for_date'];

		$rule_btn = $this->app->page_top_right_button_set->addButton('Rules')->addClass('btn btn-primary');
		$rule_btn->js('click')->univ()->frameURL('Compay Rules with Points', $this->app->url('./rules'));

		$f=$this->add('Form');
		$f->add('xepan\base\Controller_FLC')
		->showLables(true)
		->makePanelsCoppalsible(true)
		->layout([
				'for_date'=>'Filter~c1~4~closed',
				'FormButtons'=>'c2~4',
			]);
		$f->addField('DatePicker','for_date');
		$f->addSubmit('Update');


		$timesheet_m = $this->add('xepan\projects\Model_Timesheet');
		$timesheet_m->addCondition('employee_id',$this->app->employee->id);
		$timesheet_m->addCondition([['start_date',$on_date],['end_date',$on_date]]);

		$timesheet_m->acl = 'xepan\projects\Model_Task';
		$timesheet_m->setOrder('starttime','asc');


		$crud = $this->add('xepan\hr\CRUD',['allow_add'=>true]);
		$crud->setModel($timesheet_m,['task_id','starttime','endtime','duration_in_hms','remark'],['task','starttime','endtime','duration_in_hms','score','remark','start_date','end_date']);
		
		$crud->grid->removeColumn('action');
		$crud->grid->removeColumn('attachment_icon');
		$crud->grid->removeColumn('start_date');
		$crud->grid->removeColumn('end_date');


		if($crud->isEditing()){
			$crud->form->getElement('task_id')
				->getModel()
				->addCondition([['assign_to_id',$this->app->employee->id],['created_by_id',$this->app->employee->id]])
				->addCondition('status','not in',['Assigned','Completed'])
				;
		}

		if($crud->isEditing('add')){
			$crud->form->getElement('starttime')->set($this->app->now);
			$crud->form->getElement('endtime')->set($this->app->now);
		}

		$crud->grid->addHook('formatRow',function($g){
			if($g->model->IcanEdit()){
				$g->current_row_html['score'] = '<a class="timesheetedit" href="#'.$g->model->id.'" data-timesheet_id="'.$g->model->id.'">'.$g->model['score'].'</a>';
				$g->row_delete=true;
				$g->row_edit=true;
			}else{
				$g->current_row_html['score'] = $g->model['score'];
				$g->row_delete=false;
				$g->row_edit=false;
			}
		});

		$vp= $this->add('VirtualPage');
		$vp->set([$this,'manageTimeSheetScore']);

		$crud->grid->js('click')->_selector('.timesheetedit')->univ()->frameURL('Edit Score',[$vp->getURL(),['timesheet_id'=>$this->js()->_SelectorThis()->data('timesheet_id')]]);

		if($f->isSubmitted()){
			$crud->js()->reload(['for_date'=>$f['for_date']])->execute();
		}

		$this->add('H3')->set('Other Scores Given');

		$point_system_m = $this->add('xepan\base\Model_PointSystem');
		$point_system_m->addCondition('contact_id',$this->app->employee->id);
		$point_system_m->addCondition('timesheet_id',-1);
		$point_system_m->addCondition('created_at_date',$this->app->today);
		$point_system_m->getElement('created_at_date')->caption('On Date');

		$point_system_m->addHook('beforeSave',function($m){
			$m['created_by_id'] = $this->app->employee->id; // to save last updater
		});

		$grid= $this->add('xepan\hr\Grid');
		$grid->setModel($point_system_m, ['created_at_date','rule_option','qty','score','remarks','created_by']);

		$grid->addFormatter('rule_option','wrap');

	}

	function page_rules(){
		$rule_options = $this->add('xepan\base\Model_RulesOption');
		$rule_options->setOrder('rule_id');

		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($rule_options,['rule','name','description','score_per_qty']);

		$grid->addFormatter('name','wrap');
	}

	function manageTimeSheetScore($p){
		$this->app->stickyGET('timesheet_id');

		$pointsystem_m= $this->add('xepan\base\Model_PointSystem');
		$pointsystem_m->addCondition('timesheet_id',$_GET['timesheet_id']);

		$crud = $p->add('xepan\base\CRUD',['pass_acl'=>true,'allow_add'=>false,'allow_del'=>false]);
		$crud->setModel($pointsystem_m,['qty','remarks'],['rule_option','qty','score','remarks']);

		$crud->grid->addFormatter('rule_option','wrap');
		$crud->grid->addFormatter('remarks','wrap');


	}
}