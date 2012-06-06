<?php
namespace devmo\libs;
use devmo\libs\Core;
use devmo\libs\CoreException;
use devmo\controllers\Controller;

class Loader {
	private $fileBox = null;

  public function setFileBox (\devmo\libs\Box $fileBox) {
  	$this->fileBox = $fileBox;
  }

  public function getContext () {
  	return $this->fileBox->getContext();
  }

  protected function get ($path, $option='auto') {
		if ($path[0]=='.')
			$path = $this->fileBox->getContext().substr($path,1);
  	return Core::getObject($path,$option);
  }

  protected function getView ($path=null, $tokens=null) {
  	if (!($this instanceof Controller))
			throw new CoreException('ClassNotController',array('class'=>$this->fileBox->getClass(),'file'=>$this->fileBox->getFile()));
  	if (!$path)
  		$path = basename(str_replace('\\','/',$this->fileBox->getClass()));
		$fileBox = Core::getFileBox(Core::formatPath($path,'views',$this->fileBox->getContext()));
		$view = new \devmo\libs\View();
		$view->setTemplate($fileBox->file);
		if ($tokens)
			$view->setTokens($tokens);
  	return $view;
  }

}
