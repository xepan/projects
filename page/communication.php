<?php

namespace xepan\projects;

class page_communication extends \xepan\base\Page{
	function init(){
		parent::init();

		$contact_id = $this->app->stickyGET('contact_id');
		if(!$contact_id){
			$this->add('View_Error')->set('Contact Not Found');
		}

		$contact_model = $this->add('xepan\base\Model_Contact')->load($contact_id);
		$commu = $this->add('xepan\communication\View_Communication');
		$commu->setCommunicationsWith($contact_model);
	}
}