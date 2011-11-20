<?php
namespace Devmo;

abstract class Controller extends \Devmo\libraries\Loader {
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
  		throw new DevmoException('Missing Success Controller');
    $this->success = Path::getPath($controller,$this->getContext());
  }

  public function getSuccess () {
    return $this->success;
  }

  public function setFailure ($controller) {
  	if (!$controller)
  		throw new DevmoException('Missing Failure Controller');
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
  		? (Util::getValue($key,$this->data) ? $this->data[$key] : $default)
  		: $this->data;
  }

  public function addData ($key, $value) {
  	$this->data[$key] = $value;
  }

  public function setData (&$data) {
  	if (is_array($data))
  		$this->data = & $data;
  }

  abstract public function run ();
}
