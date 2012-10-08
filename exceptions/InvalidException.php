<?php
namespace devmo\exceptions;
class InvalidException extends \devmo\exceptions\Exception {
  private $field;
  private $label;
  public function __construct ($field, $value=null, $label=null, array $options=null) {
    $this->field = $field;
    $this->label = $label ?: ucfirst(preg_replace(array('/([A-Z]{1})/','/ Id/'),array(' \1',''),$field));
		$message = $value ? "Invalid {$this->label}" : "Missing {$this->label}";
		if (\devmo\Config::isDebug()) {
			$message .= ' ([';
			ob_start();
			var_dump($value);
			$message .= trim(ob_get_contents());
			ob_end_clean();
			$message .= ']';
			if ($options)
				$message .= ' options:'.implode(',',$options);
			$message .= " {$this->file}:{$this->line}) ";
		}
    parent::__construct($message);
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
