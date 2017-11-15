<?php

namespace xepan\projects;

class View_infoBoxes extends \View{
	function init(){
		parent::init();
	}

	function setModel($model){
		parent::setModel($model);
		
		$model->tryLoadAny();
				
		$this->template->trySet('val1',$model['average_receiving_time']);
		$this->template->trySet('val2',$model['average_submission_time']);
		$this->template->trySet('val3',$model['average_reacting_time']);
		$this->template->trySet('val4',0);

		$this->template->trySet('heading1','Average Receiving Time');
		$this->template->trySet('heading2','Average Submission Time');
		$this->template->trySet('heading3','Average Reacting Time');
		$this->template->trySet('heading4','Dummy');
	}

	function recursiveRender(){
		return parent::recursiveRender();
	}

	function defaultTemplate(){
		return ['view\infoboxes'];
	}
}