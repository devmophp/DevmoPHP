<?php
/**
 * Primary Controller executer.
 * Executes the current or default controller. Then
 * uses the controller execution path to execute 
 * controllers based on the returned success.
 * 
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
class Manager {




  /**
   * Executes the current controller.  Then follows 
   * the execution path to execute the followng
   * controller
   *
   * @param
   * @return
   */
  public static function execute ($name=false,$context=null,$data=null) {
  	//	get controller view
  	$path = $name ? $name : Path::getControllerPath();
    if (!$view = self::executeController($path,$context,$data))
	    throw new CoreError('ViewNotFound',array('controller'=>'?'));
    return $view;
  }
  
  
  
  
  /**
   * executeController function.
   *
   * recursive partner to execute
   *
   * @access private
   * @static
   * @param mixed $path
   * @return View
   */
  private static function executeController ($path, $context=null, $data=null, $message=null) {
    //  get controller object
    if (!$controller = Factory::getController($path,$context))
      throw new CoreError('ClassNotFound',array('mod'=>null,'controller'=>$path));
		if ($data)
			$controller->setData($data);
		if ($message)
    	$controller->setMessage($message);
    
		//	run controller
    $view = $controller->run();
    
    //  forward to next controller
    if ($controller->getForward())
      return self::executeController($controller->getForward(),$context,$data,$message);
    
    //  successful execution go to next
    if ($view===true && $controller->getSuccess()) {
      if ($controller->getSuccess()==$path)
      	throw new Error('Success Controller Can Not Equal Self ['.$path.']');
      return self::executeController($controller->getSuccess(),$context,null,$controller->getMessage());

    //  unsuccessful execution
    } else if ($view===false && $controller->getFailure()) {
      if ($controller->getFailure()==$path)
      	throw new Error('Error Controller Can Not Equal Self ['.$path.']');
      return self::executeController($controller->getFailure(),$context,null,$controller->getMessage());

    } else if ($view instanceof View) {
    	return $view;
    }

  }

} // EOC
