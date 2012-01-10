<?php
namespace devmo\controllers;

use \devmo\libs\Core;
use \devmo\exceptions\Exception;
use \Devmo;

abstract class Controller extends \devmo\libs\Loader {
  protected $success = null;
  protected $failure = null;
  protected $forward = null;
  protected $do = null;
	protected $message = null;
	protected $data = array();
  protected $ajax = false;

	public function setAjax ($ajax) {
		$this->ajax = $ajax;
	}

	public function isAjax () {
		return $this->ajax;
	}

  public function setSuccess ($controller) {
  	if (!$controller)
  		throw new Exception('Missing Success Controller');
    $this->success = Path::getPath($controller,$this->getContext());
  }

  public function getSuccess () {
    return $this->success;
  }

  public function setFailure ($controller) {
  	if (!$controller)
  		throw new Exception('Missing Failure Controller');
    $this->failure = Path::getPath($controller,$this->getContext());
  }

  public function getFailure () {
    return $this->failure;
  }

  public function setForward ($forward) {
  	$this->forward = $forward;
  }

  public function getForward () {
    return $this->forward;
  }

  public function getDo () {
    return $this->do;
  }

  public function setMessage ($message) {
  	$this->message = $message;
  }

  public function getMessage () {
  	return $this->message;
  }

  public function getData ($key=null, $default=null) {
  	return $key
  		? (Devmo::getValue($key,$this->data) ? $this->data[$key] : $default)
  		: $this->data;
  }

  public function addData ($key, $value) {
  	$this->data[$key] = $value;
  }

  public function setData (&$data) {
  	if (is_array($data))
  		$this->data = & $data;
  }

	protected function getGet ($name, $makeSafe=true) {
		return (($value = Devmo::getValue($name,$_GET)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getPost ($name, $makeSafe=true) {
		return (($value = Devmo::getValue($name,$_POST)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getSession ($name) {
		return Devmo::getValue($name,$_SESSION);
	}

	protected function getRequest ($name, $makeSafe=true) {
		return (($value = Devmo::getValue($name,$_REQUEST)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}
	
	protected function getRequestController () {
		return Core::$requestedController
			? Core::$requestedController
			: Core::$homeController;
	}

	protected static function getServer ($name) {
		return Devmo::getValue($name,$_SERVER);
	}
	
  protected function runController ($path, $data=null) {
  	return Core::execute(Core::formatPath($path,'controllers'),$data);
  }
	
	protected function runRequest ($request) {
		return Core::execute(Core::formatRequestToPath($request));
	}

  abstract public function run ();
}
