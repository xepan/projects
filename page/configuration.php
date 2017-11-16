<?php
namespace xepan\projects;
class page_configuration extends \xepan\base\Page{
	public $title = "Configuration";
	function init(){
		parent::init();
		$this->app->side_menu->addItem(['Layouts','icon'=>'fa fa-th'],'xepan_projects_layout')->setAttr(['title'=>'Layouts']);
	}
}