<?php
namespace devmo\controllers;

use \Devmo;
use \devmo\libs\Core;
use \devmo\libs\Config;
use \devmo\exceptions\Exception;


abstract class Controller extends \devmo\libs\Loader {
  protected $forward = null;
  protected $do = null;
	protected $message = null;
  protected $ajax = false;

	public function setAjax ($ajax) {
		$this->ajax = $ajax;
	}

	public function isAjax () {
		return $this->ajax;
	}

  public function setForward ($controller) {
  	$this->forward = Core::formatPath($controller,'controllers',$this->getContext());
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
		if (!isset($_SESSION))
			throw new \devmo\exceptions\Exception('session does not exist');
		return Devmo::getValue($name,$_SESSION,$default);
	}

	protected function getRequest ($name, $default=false, $makeSafe=true) {
		return (($value = Devmo::getValue($name,$_REQUEST,$default)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getValue ($key, $mixed, $default=false) {
		return Devmo::getValue($key,$mixed,$default);
	}

	protected function getRequestController () {
		return Config::getRequestedController()
			? Config::getRequestedController()
			: Config::getDefaultController();
	}

	protected static function getServer ($name, $default=false) {
		return Devmo::getValue($name,$_SERVER,$default);
	}

  protected function runController ($controller, $args=null) {
  	return Core::execute(Core::formatPath($controller,'controllers',$this->getContext()),$args);
  }

	protected function runRequest ($request, $args=null) {
		return Core::execute(Core::formatRequestToPath($request),$args);
	}

	protected function debug ($mixed, $title=null, $option=null) {
		Devmo::debug($mixed,$title,$option);
	}

  abstract public function run (array $args=null);
}
