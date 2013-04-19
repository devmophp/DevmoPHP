<?php
namespace devmo\exceptions;
class InvalidException extends \devmo\exceptions\Exception {
  private $name;
	private $value;
	private $options;
  public function __construct ($name, $value=null, $options=null) {
		$this->setName($name);
		$this->setValue($value);
		$this->setOptions($options);
		$message = $this->value ? "Invalid {$this->name}" : "Missing {$this->name}";
		if (\devmo\Config::isDebug())
			$message .= ($this->value?' value:"'.print_r($this->value,true).'"':'').($this->options?' options:"'.implode('","',$this->options).'"':null)." {$this->file}:{$this->line}";
    parent::__construct($message);
  }
	private function setName ($name) {
		$this->name = trim($name);
	}
	private function setValue ($value) {
		$this->value = $value;
	}
	private function setOptions ($options) {
		$this->options = (is_array($options) ? $options : ($options ? array($options) : null));
	}
	public function getName () {
		return $this->name;
	}
	public function getValue () {
		return $this->value;
	}
	public function getOptions ($toString=false) {
		return $toString ? implode(',',$this->options) : $this->options;
	}
}
