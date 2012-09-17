<?php
namespace devmo\exceptions;
class FileNotFoundCoreException extends \devmo\exceptions\CoreException {
  public function __construct ($path, $request=null) {
    parent::__construct('FileNotFound',array('path'=>$path,'request'=>$request));
  }
}
