<?php
namespace devmo\exceptions;
class InvalidException extends \devmo\exceptions\Exception {
  private $field;
  private $label;
  public function __construct ($field, $value=null, $label=null) {
    $this->field = $field;
    $this->label = $label ?: $field;
    parent::__construct(($value ? "Invalid value found for: {$this->label}".(\devmo\Config::isDebug()?":{$value}":null) : "Missing value for: {$this->label}"));
  }
	public function getField () {
		return $this->field;
	}
	public function getLabel () {
		return $this->label;
	}
	public function setLabel ($label) {
		$this->label = $label;
	}
}
