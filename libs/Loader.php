<?php
namespace devmo\libs;
use devmo\libs\Core;
use devmo\libs\CoreException;
use devmo\controllers\Controller;

class Loader {
	private $context = null;
	private $fileBox = null;

  public function setFileBox (\devmo\libs\Box $fileBox) {
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

  protected function get ($path, $option='auto') {
		if ($path[0]=='.')
			$path = $this->fileBox->getContext().substr($path,1);
  	return Core::getObject($path,$option);
  }

	protected function getController ($path) {
		return $this->get(Core::formatPath($path,'controllers',$this->fileBox->getContext()));
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

	protected function getDao ($path) {
		return $this->get(Core::formatPath($path,'daos',$this->fileBox->getContext()));
	}

	protected function getDto ($path) {
		return $this->get(Core::formatPath($path,'dtos',$this->fileBox->getContext()));
	}

	protected function getLibrary ($path) {
		return $this->get(Core::formatPath($path,'libs',$this->fileBox->getContext()));
	}

	protected function getInclude ($path) {
		return $this->get(Core::formatPath($path,'includes',$this->fileBox->getContext()),'load');
	}

}
