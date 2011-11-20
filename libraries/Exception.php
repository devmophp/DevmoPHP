<?php
namespace Devmo;

class Exception extends \LogicException {
  private $path;
  private $info;


  public function __construct ($text, $path=null) {
    $this->path = $path;
    $this->extra = null;
    parent::__construct($text);
  }


  public function getPath () {
    return $this->path;
  }

  
  public function setInfo ($info) {
    $this->info = $info;
  }


  public function __toString () {
    $err = "\nWhat: ".$this->getMessage()
         .($this->info ? "\nInfo: {$this->info}" : null)
         .($this->path ? "\nPath: {$this->path}" : null)
         . "\nWhen: ".date('Y-m-d H:m:s')
         . "\nWhere: ";
    foreach ($this->getTrace() as $i=>$x) {
      $err .= ($i>0?"       ":null)
           .  (isset($x['file'])?$x['file']:null)
           .  ":" .(isset($x['line'])?$x['line']:null)
           .  " " 
           .  (isset($x['class'])?$x['class']:null)
           .  (isset($x['type']) ?$x['type']:null)
           .  (isset($x['function'])?$x['function']:null)
           #.  (isset($x['args'])?"(".implode(',',$x['args']).")":null)
           .  "\n";
    }
    return $err;
  }  
  
}


class CoreException extends \Devmo\Exception {
  public $controller;
  public $tokens;


  public function __construct ($controller=null,$tokens=null) {
    $this->controller = $controller;
    $this->tokens = $tokens ? $tokens : array();
    $this->tokens['controller'] = $controller;
    parent::__construct("DevmoCoreException:{$controller}");
  }
  

  public function __toString () {
    $info = "Controller:{$this->controller}";
    foreach ($this->tokens as $k=>$v)
      if ($v!=null)
        $info .= " ".ucfirst($k).":{$v}";
    parent::setInfo($info);
    return parent::__toString();
  }

}



class UniqueException extends \Devmo\Exception {
}


class InvalidException extends \Devmo\Exception {
  public function __construct ($what,$value) {
    parent::__construct(($value ? "Invalid Value Found For {$what}" : "Missing Value For {$what}"));
  }
}
