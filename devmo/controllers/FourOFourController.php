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
    $view = Factory::getView('DefaultWrapper');
    $view->setToken('body',Factory::getView('/FourOFour'));
    return $view;
  }
  
}
