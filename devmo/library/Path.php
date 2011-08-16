<?php
/**
 * Finds requested files first starting in
 * the current context directory and then working 
 * back to the application root directory
 *
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @category Framework
 * @version 1.0
 */
class Path {
	private static $urlController = null;
	private static $initController = null;
	private static $initContext = null;
	private static $map = null;


  public static function init () {
		self::$initController = (defined('DEVMO_REQUESTED_CONTROLLER') && DEVMO_REQUESTED_CONTROLLER && DEVMO_REQUESTED_CONTROLLER!='/')
			? self::getMappedController(DEVMO_REQUESTED_CONTROLLER)
			: DEVMO_HOME_CONTROLLER;
 		self::$initContext = self::getContext(self::$initController);
 		if (!defined('DEVMO_CONTROLLER_SUBDIR'))
 			define('DEVMO_CONTROLLER_SUBDIR','controllers');
 		if (!defined('DEVMO_DAO_SUBDIR'))
 			define('DEVMO_DAO_SUBDIR','daos');
 		if (!defined('DEVMO_LIBRARY_SUBDIR'))
 			define('DEVMO_LIBRARY_SUBDIR','library');
 		if (!defined('DEVMO_VIEW_SUBDIR'))
 			define('DEVMO_VIEW_SUBDIR','views');
  }

  public static function setControllerMap (&$map) {
  	if (is_array($map))
  		self::$map =& $map;
  }

  private static function getMappedController ($path) {
  	self::$urlController = $path;
  	if (self::$map) {
  		foreach (self::$map as $pattern=>$controller) {
  			if (preg_match($pattern,$path)) {
  				$path = $controller;
  				break;
  			}
  		}
  	}
  	return $path;
  }
  
  public static function getUrlController () {
  	return self::$urlController;
  }
  
  public static function getContext ($path) {
  	return preg_replace('=^(.*/)[^/]+$=','\1',$path);
  }


  public static function getPath ($file, $context=null) {
  	if (!$file)
  		throw new Error('Missing File');
    if ($file && substr($file,0,1)=='/')
  		return $file;
  	if ($context)
  		return $context.$file;
 		return self::$initContext.$file;
  }


  public static function getControllerPath ($controller=null) {
    return $controller
    	? self::getPath($controller)
    	: self::$initController;
  }


  /**
   * Main method for framework to find path of file
   *
   * @param file name, directory name, search options
   * @return absolute path to file
   */
  public static function getFile ($base, $folder, $fwkFolder, $path, $ucfirst=false) {
  	$matches = array();
  	if (!preg_match('=^(.*?)([^/]+)$=',$path,$matches))
			throw new Error("Problems finding file path [{$path}]");
		$matches[2] = ($ucfirst ? ucfirst($matches[2]) : $matches[2]).'.php';
  	$path = $base
  		.($matches[1] ? substr($matches[1],0,-1) : null)
  		.($folder ? "/{$folder}/" : '/')	//.'/self/'.$folder.'/'
  		.$matches[2];
    //  check framwork core
    if (!is_file($path)) {
    	$fwkPath = DEVMO_DIR.'/'.$fwkFolder.'/'.$matches[2];
    	if (is_file($fwkPath))
		    $path = $fwkPath;
    }
    //  handle file not found
    if (!is_file($path))
      throw new CoreError('/FileNotFound',array('file'=>$path));
    return $path;
  }

}	//	EOC
?>