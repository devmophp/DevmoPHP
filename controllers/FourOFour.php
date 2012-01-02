<?php
/**
 * Default Message for handling a file not found.
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
namespace devmo\controllers;
class FourOFour extends \devmo\controllers\Controller {

  public function run () {
  	header("HTTP/1.0 404 Not Found");
    $view = $this->getView('/SiteWrapper');
    $view->body = $this->getView();
    return $view;
  }
  
}
