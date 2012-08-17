<?php
namespace devmo\controllers;
/**
 * handler for all CoreExceptions
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
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
    $wrap = $this->runController('devmo.SiteWrapper',array('title'=>'Problems!','body'=>$view));
    return $wrap;
  }

	public function setException ($exception) {
		$this->exception = $exception;
	}

}
