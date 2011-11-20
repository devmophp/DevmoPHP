<?php
namespace Devmo\libraries;

class Loader {
	private $context = null;

  public function setContext ($context) {
  	$this->context = $context;
  }

  public function getContext () {
  	return $this->context;
  }

  private function addContextToPath ($path) {
  	return ($this->context && substr($path,0,1)!='/')
  		? $this->context.$path
  		: $path;
  }

  protected function getView ($template=null, $tokens=null) {
  	if (!$template) {
  		$template = preg_replace(
  			array('=\\\=','=(/?)([a-zA-Z0-9]*)[cC]ontroller[s]?=','=[/]{2,}='),
  			array('/','\1\2','/'),
  			'/'.get_class($this));
		}
		$view = \Devmo::getView($template,$tokens);
  	return $view;
  }

  protected function getController ($controller) {
  	return \Devmo::getController($this->addContextToPath($controller));
  }

  protected function runController ($controller, $data=null) {
  	return \Devmo\Core::execute($this->addContextToPath($controller),$data);
  }

  protected function getDao ($dao) {
  	return \Devmo::getDao($this->addContextToPath($dao));
  }

  protected function getLibrary ($library, $option='auto') {
  	return \Devmo::getLibrary($this->addContextToPath($library),$option);
  }

}
