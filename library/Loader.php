<?php
class Loader {
	private $context = null;

  public function setContext ($context) {
  	$this->context = $context;
  }

  public function getContext () {
  	return $this->context;
  }

  private function addContextToPath ($path) {
    if ($path && substr($path,0,1)=='/')
  		return $path;
  	if ($this->context)
  		return $this->context.$path;
 		return self::$initContext.$file;
  }

  protected function getView ($template=null, $tokens=null) {
  	if (!$template)
  		$template = str_replace('Controller','',get_class($this));
		$view = Devmo::getView($this->addContextToPath($template),$tokens);
  	return $view;
  }

  protected function getController ($controller) {
  	return Devmo::getController($this->addContextToPath($controller));
  }

  protected function runController ($controller, $data=null) {
  	return DevmoCore::execute($this->addContextToPath($controller),$data);
  }

  protected function getDao ($dao) {
  	return Devmo::getDao($this->addContextToPath($dao));
  }

  protected function getLibrary ($library, $option='auto') {
  	return Devmo::getLibrary($this->addContextToPath($library),$option);
  }

}
