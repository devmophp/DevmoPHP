<?php
/**
 * Container for global functions
 * used throughout the framework
 * no dependencies allowed please
 *
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @category Utility
 * @version 1.0
 */
class Util {
  
  
  
  
  /**
   * Used to return the value of an associative array
   * this is to avoid the strict warnings by checking for the existance
   * with the isset() method for you
   *
   * @param key name, associative array
   * @return value of key
   */
  public static function getValue ($name, $mixed) {
  	if (is_array($mixed))
			return isset($mixed[$name])
	      ? $mixed[$name]
  	    : false;
		if (is_object($mixed))
			return isset($mixed->{$name})
				? $mixed->{$name}
				: false;
  }
  
  
  
  
  /**
   * Used to return the value of a Session variable
   * this is to avoid the strict warnings by checking for the existance
   * with the isset() method for you
   *
   * @param key name, associative array
   * @return value of key
   */
  public static function getSession ($name) {
    return self::getValue($name,$_SESSION);
  }
  
  
  
  
  /**
   * Used to return the value of a GET request
   * this is to avoid the strict warnings by checking for the existance
   * with the isset() method for you
   *
   * @param key name, associative array
   * @return value of key
   */
  public static function getGet ($name) {
    return self::getValue($name,$_GET);
  }
  
  
  
  
  /**
   * Used to return the value of a POST request
   * this is to avoid the strict warnings by checking for the existance
   * with the isset() method for you
   *
   * @param key name, associative array
   * @return value of key
   */
  public static function getPost ($name) {
    return self::getValue($name,$_POST);
  }
    
  
  
  
  /**
   * Primarily used to get the framework compliant url
   * for the wanted controller
   *
   * @param controller name, get variables, secure socket
   * @return url to controller
   */
  public static function getUrl ($controller=null, $vars=null, $ssl=false, $domain=null) {
    //  handle GET vars
    $get = null;
    if (is_array($vars))
      foreach ($vars as $k=>$v)
        $get .= ($get ? '&' : '?') . urlencode($k) . '=' . urlencode($v);
    //  return url
    $url = $_SERVER['SCRIPT_NAME'].Path::getControllerPath($controller).$get;
    if ($ssl && (empty($_SERVER['HTTPS']) || !empty($domain)))
    	$url = 'https://'.($domain?$domain:$_SERVER['HTTP_HOST']).$url;
    return $url;
  }
    
  
  
  
  /**
   * Used in controllers to check against requested controller
   *
   * @return requested controller path
   */
  public static function getController () {
  	return Path::getControllerPath();
  }
	
}
?>