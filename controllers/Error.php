<?php
/**
 * Default base controller for creating the wrapper around errors
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
namespace devmo\controllers;
class Error extends \devmo\controllers\Controller {
	public $exception;

  public function run (array $args=null) {
		// log it
    $message = "Error:";
		foreach ($args as $k=>$v)
			$message .= " {$k}:{$v}";
    // build wrapper
    $error = $this->getView("devmo.views.{$this->exception->name}Error",$args);
    $view = $this->getView('devmo.views.Error',array('body'=>$error,'trace'=>$this->exception->__toViewString()));
    $wrap = $this->runController('devmo.SiteWrapper',array('body'=>$view));
    return $wrap;
  }

	public function setException ($exception) {
		$this->exception = $exception;
	}

}
