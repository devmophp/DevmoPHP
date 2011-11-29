<?php
namespace Devmo\libs;
use Devmo\libs\Core;

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

  protected function get ($name, $option='auto') {
  	return Core::getObject($name);
  }

  protected function runController ($controller, $data=null) {
  	return Core::execute($controller,$data);
  }

}
