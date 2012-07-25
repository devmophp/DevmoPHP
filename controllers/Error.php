<?php
namespace devmo\controllers;
/**
 * Default base controller for creating the wrapper around errors
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
		$view = null;
		if (Config::isCli()) {
			$view = $this->getView('devmo.echo');
			$view->string = $this->exception;
		} else {
			$error = $this->getView("devmo.views.{$this->exception->name}Error",$args);
			$view = $this->getView('devmo.views.Error',array('body'=>$error,'trace'=>$this->exception->__toViewString()));
			$view = $this->runController('devmo.SiteWrapper',array('title'=>'Problems!','body'=>$view));
		}
		return $view;
  }

	public function setException ($exception) {
		$this->exception = $exception;
	}

}
