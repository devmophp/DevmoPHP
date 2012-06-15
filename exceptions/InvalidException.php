<?php
namespace devmo\exceptions;
class InvalidException extends \devmo\exceptions\Exception {
  private $field;
  private $label;
  public function __construct ($field, $value=null, $label=null) {
    $this->field = $field;
    $this->label = $label ?: $field;
    parent::__construct(($value ? "Invalid {$this->label}".(\devmo\Config::isDebug()?" [{$value}]":null) : "Missing {$this->label}")." ({$this->file}:{$this->line})");
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
