<?php
/**
 * Default base controller for creating the wrapper around errors
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
namespace Devmo\controllers;
class Error extends \Devmo\controllers\Controller {
	public $exception;

  public function run () {
		// log it
    $message = "Error:";
		foreach ($this->getData() as $k=>$v)
			$message .= " {$k}:{$v}";
    \Devmo\libs\Logger::add($message);
    // build wrapper
    $error = $this->getView("Devmo.views.{$this->exception->error}Error",$this->getData());
    $view = $this->getView('Devmo.views.Error',array('body'=>$error));
    $wrap = $this->runController('Devmo.controllers.SiteWrapper',array('body'=>$view));
    return $wrap;
  }
	
	public function setException ($exception) {
		$this->exception = $exception;
	}

}
