<?php
/**
 * Default Message for handling a file not found.
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
class FourOFourController extends Controller {

  public function run () {
    $view = $this->getView('/SiteWrapper');
    $view->body = $this->getView();
    return $view;
  }
  
}
