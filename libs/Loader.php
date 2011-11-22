<?php
namespace Devmo\libs;

class Loader {
	private $context = null;
	private $fileBox = null;

  public function setFileBox (\Devmo\libs\Box $fileBox) {
  	$this->fileBox = $fileBox;
  }

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
  	if (!$template)
  		$template = basename(str_replace('\\','/',$this->fileBox->class));
		if (!strstr($template,'.'))
			$template = $this->fileBox->context.'views.'.$template;
		$fileBox = Core::getFileBox($template);
		$view = new \Devmo\libs\View();
		$view->setTemplate($fileBox->file);
		if ($tokens)
			$view->setTokens($tokens);
  	return $view;
  }

  protected function getController ($controller) {
  	return \Devmo::getController($this->addContextToPath($controller));
  }

  protected function runController ($controller, $data=null) {
  	return \Devmo\libs\Core::execute($this->addContextToPath($controller),$data);
  }

  protected function getDao ($dao) {
  	return \Devmo::getDao($this->addContextToPath($dao));
  }

  protected function getLibrary ($library, $option='auto') {
  	return \Devmo::getLibrary($this->addContextToPath($library),$option);
  }

}
