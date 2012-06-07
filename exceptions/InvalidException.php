<?php
namespace devmo\exceptions;

class InvalidException extends \devmo\exceptions\Exception {
  private $what;
  private $field;

  public function __construct ($what, $value=null, $field=null) {
    $this->what = $what;
    $this->field = $field;
    parent::__construct(($value ? "Invalid Value Found For {$what}".(\devmo\Config::isDebug()?":{$value}":null) : "Missing Value For {$what}"));
  }

	public function getWhat () {
		return $this->what;
	}

	public function setField ($field) {
		$this->field = $field;
	}

	public function getField () {
		return $this->field;
	}
}
