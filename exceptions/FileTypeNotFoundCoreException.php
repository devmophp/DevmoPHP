<?php
namespace devmo\exceptions;
use \devmo\Config;
class FileTypeNotFoundCoreException extends \devmo\exceptions\CoreException {
  public function __construct ($type, $path) {
    parent::__construct('FileTypeNotFound',array('type'=>$type,'path'=>$path,'types'=>implode(',',Config::getTypes())));
  }
}
