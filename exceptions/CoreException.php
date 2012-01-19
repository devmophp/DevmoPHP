<?php
namespace devmo\exceptions;

class CoreException extends \devmo\exceptions\Exception {
  public $name;
  public $tokens;


  public function __construct ($name=null, $tokens=null) {
    $this->name = $name;
    $this->tokens = is_array($tokens) ? $tokens : array();
    parent::__construct("Core::{$name}");
  }
  

  public function __toString () {
    $info = "";
    foreach ($this->tokens as $k=>$v)
	    $info .= (empty($info)?null:', ').ucfirst($k)."={$v}";
		parent::setInfo($info);
    return parent::__toString();
  }

}
