<?php
namespace devmo\exceptions;

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
    $err = "What: ".$this->getMessage()
				 . PHP_EOL."When: ".date('Y-m-d H:m:s')
         .($this->path ? PHP_EOL."Path: {$this->path}" : null)
         .($this->info ? PHP_EOL."Info: {$this->info}" : null)
				 .PHP_EOL."Where: {$this->file}:{$this->line} [{$this->code}]";
    foreach ($this->getTrace() as $i=>$x) {
      $err .= PHP_EOL
           .(isset($x['file'])?$x['file']:null)
           .(isset($x['line'])?":{$x['line']} ":" ")
           .(isset($x['class'])?$x['class']:null)
           .(isset($x['type'])?$x['type']:null)
           .(isset($x['function'])?$x['function']:null);
    }
		$err .= PHP_EOL;
    return $err;
  }


  public function __toViewString () {
    $err = "What: ".$this->getMessage()
				 . PHP_EOL."When: ".date('Y-m-d H:m:s')
         .($this->path ? PHP_EOL."Path: {$this->path}" : null)
         .($this->info ? PHP_EOL."Info: {$this->info}" : null)
				 .PHP_EOL."Where: {$this->file}:{$this->line} [{$this->code}]";
    foreach ($this->getTrace() as $i=>$x) {
			if (strpos($x['class'],'devmo\\')===0)
				continue;
      $err .= PHP_EOL
           .(isset($x['file'])?$x['file']:null)
           .(isset($x['line'])?":{$x['line']} ":" ")
           .(isset($x['class'])?$x['class']:null)
           .(isset($x['type'])?$x['type']:null)
           .(isset($x['function'])?$x['function']:null);
    }
		$err .= PHP_EOL;
    return $err;
  }

}
