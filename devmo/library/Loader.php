<?php
class Loader {
	private $context = null;
  
  public function setContext ($context) {
  	$this->context = $context;
  }
  
  public function getContext () {
  	return $this->context;
  }

  protected function getView ($template=null, $tokens=null) {
  	if (!$template)
  		$template = str_replace('Controller','',get_class($this));
  	$view = Factory::getView(Path::getPath($template,$this->getContext()));
  	if (is_array($tokens))
  		$view->setTokens($tokens);
  	return $view;
  } 
  
  protected function getController ($controller) {
  	return Factory::getController($controller,$this->getContext());
  }
  
  protected function runController ($controller, $data=null) {
  	return Manager::execute($controller,$this->getContext(),$data);
  }

  protected function getDao ($dao) {
  	return Factory::getDao($dao,$this->getContext());
  }

  protected function getLibrary ($library, $option='auto') {
  	return Factory::getLibrary($library,$this->getContext(),$option);
  } 

}
