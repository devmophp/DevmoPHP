<?php
/**
 * official powered by view controller
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
namespace devmo\controllers;

class PoweredBy extends \devmo\controllers\Controller {

  public function run (array $args=null) {
    return $this->getView('devmo.PoweredBy');
  }

}
