<?php
/**
 * Default base controller for creating the wrapper around errors
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
class ErrorController extends Controller {
	public $template;

  public function run () {
    //  build wrapper
    $error = $this->getView("/{$this->template}Error",$this->getData());
    $view = $this->getView('/Error',array('body'=>$error));
    $wrap = $this->runController('/Default',array('body'=>$view));
    return $wrap;
  }
  
}
?>
