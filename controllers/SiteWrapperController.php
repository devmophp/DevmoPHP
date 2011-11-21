<?php
/**
 * base controller for creating the default wrapper
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
namespace Devmo\controllers;

class SiteWrapperController extends \Devmo\controllers\Controller {

  public function run () {
    return $this->getView('/SiteWrapper',$this->getData());
  }
  
}
