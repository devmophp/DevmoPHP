<?php
namespace devmo\controllers;

use \Devmo;
use \devmo\libs\Core;
use \devmo\libs\Config;
use \devmo\exceptions\Exception;


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

	protected function getGet ($name, $default=false, $makeSafe=true) {
		return (($value = Devmo::getValue($name,$_GET,$default)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getPost ($name, $default=false, $makeSafe=true) {
		return (($value = Devmo::getValue($name,$_POST,$default)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getSession ($name, $default=false) {
		return Devmo::getValue($name,$_SESSION,$default);
	}

	protected function getRequest ($name, $default=false, $makeSafe=true) {
		return (($value = Devmo::getValue($name,$_REQUEST,$default)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getRequestController () {
		return Config::getRequestedController()
			? Config::getRequestedController()
			: Config::getDefaultController();
	}

	protected static function getServer ($name, $default=false) {
		return Devmo::getValue($name,$_SERVER,$default);
	}

  protected function runController ($path, $data=null) {
  	return Core::execute(Core::formatPath($path,'controllers'),$data);
  }

	protected function runRequest ($request, $data=null) {
		return Core::execute(Core::formatRequestToPath($request),$data);
	}

  abstract public function run ();
}
