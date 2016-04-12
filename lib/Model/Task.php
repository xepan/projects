<?php

namespace xepan\projects;

class Model_Task extends \xepan\base\Model_Table
{	
	public $table = "task";
	public $title_field ='task_name';

	public $status=['Pending','Assigned','Submitted','On-Hold','Completed','Reopened'];

	public $actions =[
		'Submitted'=>['view','edit','delete','assign','mark_complete','onhold'],
		'Assigned'=>['view','edit','delete','submit','mark_complete','onhold'],
		'Completed'=>['view','edit','delete','submit','assign','re_open'],
		'Pending'=>['view','edit','delete','submit','assign','mark_complete','onhold'],
		'On-Hold'=>['view','edit','delete','submit','assign','mark_complete'],
	];
	
	function init()
	{
		parent::init();

		$this->hasOne('xepan\base\Epan');
		$this->hasOne('xepan\projects\Project','project_id');
		$this->hasOne('xepan\projects\ParentTask','parent_id');
		$this->hasOne('xepan\hr\Employee','employee_id');
		$this->addField('task_name');
		$this->addField('description')->type('text');
		$this->addField('deadline')->type('date');
		$this->addField('starting_date')->type('date');
		$this->addField('estimate_time')->display(['form'=>'TimePicker']);
		
		$this->addField('status')->defaultValue('Pending');
		$this->addField('type');
		$this->addField('priority')->setValueList(['25'=>'Low','50'=>'Medium','75'=>'High','90'=>'Critical'])->EmptyText('Priority')->defaultValue('Medium');
		$this->addCondition('type','Task');

		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
		$this->hasOne('xepan\hr\Employee','created_by_id');

		$this->hasMany('xepan\projects\Follower_Task_Association','task_id');
		$this->hasMany('xepan\projects\Comment','task_id');	
		$this->hasMany('xepan\projects\Task','parent_id',null,'SubTasks');

		$this->addHook('beforeDelete',$this);

		$this->is([
			'task_name|required'
			]);

		$this->addExpression('follower_count')->set(function($m){
			return $m->refSQL('xepan\projects\Follower_Task_Association')->count();
		});

	}

	function beforedelete(){
		$sub_task=$this->add('xepan\projects\Model_Task');
		$sub_task->addCondition('parent_id',$this->id);
		$sub_task->tryLoadAny();

		if($sub_task->count()->getOne()){
			throw new \Exception("Can'not Delete Task Its has Contains Many First delete Sub task", 1);
			
		}
	}

	function submit(){
	}

	function assign(){
		
	}

	function mark_complete(){		
		$this['status']='Completed';
		$this->save();
	}

	function re_open(){		
		$this['status']='Pending';
		$this->save();
	}


	function getAssociatedfollowers(){
		$associated_followers = $this->ref('xepan\projects\Follower_Task_Association')
								->_dsql()->del('fields')->field('employee_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_followers)),false);
	}

	function removeAssociateFollowers(){
		$this->ref('xepan\projects\Follower_Task_Association')->deleteAll();
	}
}