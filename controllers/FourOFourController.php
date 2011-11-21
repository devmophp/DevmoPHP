<?php
/**
 * Default Message for handling a file not found.
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
namespace Devmo\controllers;
class FourOFourController extends \Devmo\controllers\Controller {

  public function run () {
    $view = $this->getView('/SiteWrapper');
    $view->body = $this->getView();
    return $view;
  }
  
}
