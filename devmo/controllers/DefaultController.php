<?php
/**
 * Default base controller for creating the wrapper around errors
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
class DefaultController extends Controller {

  public function run () {
    return $this->getView('/DefaultWrapper',$this->getData());
  }
  
}
?>
