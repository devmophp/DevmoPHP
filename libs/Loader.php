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
			$path = $this->fileBox->context.substr($path,1);
  	return Core::getObject($path,$option);
  }

	protected function getController ($path) {
		return $this->get(Core::formatPath($path,'controllers',$this->fileBox->context));
	}

  protected function getView ($path=null, $tokens=null) {
  	if (!($this instanceof Controller))
			throw new CoreException('ClassNotController',array('class'=>$this->fileBox->class,'file'=>$this->fileBox->file));
  	if (!$path)
  		$path = basename(str_replace('\\','/',$this->fileBox->class));
		$fileBox = Core::getFileBox(Core::formatPath($path,'views',$this->fileBox->context));
		$view = new \devmo\libs\View();
		$view->setTemplate($fileBox->file);
		if ($tokens)
			$view->setTokens($tokens);
  	return $view;
  }

	protected function getDao ($path) {
		return $this->get(Core::formatPath($path,'daos',$this->fileBox->context));
	}

	protected function getDto ($path) {
		return $this->get(Core::formatPath($path,'dtos',$this->fileBox->context));
	}

	protected function getLibrary ($path) {
		return $this->get(Core::formatPath($path,'libs',$this->fileBox->context));
	}

	protected function getInclude ($path) {
		return $this->get(Core::formatPath($path,'includes',$this->fileBox->context),'load');
	}

}
