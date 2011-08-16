<?php
class HomeController extends Controller {
	public function run () {
		Logger::add("hi dan");
		return $this->runController('/SiteWrapper',array('body'=>$this->getView()));
	}
}