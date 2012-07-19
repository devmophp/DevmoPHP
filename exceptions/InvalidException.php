<?php
namespace devmo\exceptions;
class InvalidException extends \devmo\exceptions\Exception {
  private $field;
  private $label;
  public function __construct ($field, $value=null, $label=null) {
    $this->field = $field;
    $this->label = $label ?: ucfirst(preg_replace(array('/([A-Z]{1})/','/ Id/'),array(' \1',''),$field));
		$debug = "";
		if (\devmo\Config::isDebug()) {
			$debug = ' [';
			ob_start();
			var_dump($value);
			$debug .= ob_get_contents();
			ob_end_clean();
			$debug .= "] ({$this->file}:{$this->line})";
		}
    parent::__construct(($value ? "Invalid {$this->label}" : "Missing {$this->label}").$debug);
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
