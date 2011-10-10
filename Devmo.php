<?php
/**
 * Main initializer for essentail properties
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
class Devmo {

	public static function run () {
    try {
      echo DevmoCore::execute()->getRoot();
    } catch (DevmoCoreException $e) {
    	if (DevmoCore::$debug) {
    		$controller = Devmo::getController('/Error');
    		$controller->template = $e->controller;
    		$controller->setData($e->tokens);
      	echo $controller->run();
			} else {
		  	header("HTTP/1.0 404 Not Found");
      	echo DevmoCore::execute('/FourOFour')->getRoot();
			}
    }
  }


	public static function setAppPath ($path) {
		foreach (DevmoCore::$paths as $k=>$v) {
			DevmoCore::$paths[$k] = array($path);
		}
	}


	public static function addAppPath ($path) {
		foreach (DevmoCore::$paths as $k=>$v) {
			DevmoCore::$paths[$k][] = $path;
		}
	}


	public static function addControllerPath ($path) {
		DevmoCore::$paths['controllers'][] = $path;
	}


	public static function addViewPath ($path) {
		DevmoCore::$paths['views'][] = $path;
	}


	public static function addLibraryPath ($path) {
		DevmoCore::$paths['libraries'][] = $path;
	}


	public static function addDaoPath ($path) {
		DevmoCore::$paths['daos'][] = $path;
	}


	public static function addMapping ($mapping) {
		DevmoCore::$mappings[] = $mapping;
	}


	public static function setDebug ($debug=false) {
		DevmoCore::$debug = ($debug==true);
	}


	public static function setLog ($file) {
		Logger::setDefaultFile($file);
	}


	public static function setHomeController ($controller) {
		DevmoCore::$homeController = $controller;
	}


	public static function setRequestedController ($controller) {
		DevmoCore::$requestedController = $controller;
	}


  public static function getValue ($name, &$mixed=null) {
  	if (is_array($mixed))
			return isset($mixed[$name])
	      ? $mixed[$name]
  	    : false;
		if (is_object($mixed))
			return isset($mixed->{$name})
				? $mixed->{$name}
				: false;
  }


  public static function getSession ($name) {
    return self::getValue($name,$_SESSION);
  }


  public static function getGet ($name) {
    return self::getValue($name,$_GET);
  }


  public static function getPost ($name) {
    return self::getValue($name,$_POST);
  }


  public static function getRequest ($name) {
    return self::getValue($name,$_REQUEST);
  }
	
	
  public static function getServer ($name) {
    return self::getValue($name,$_SERVER);
  }
  
  /**
   * Returns the current debug setting
   * 
   * @return bool Whether debug is on or off
   */
  public static function isDebug() {
  	return DevmoCore::$debug;
  }


  public static function getController ($controller, $loadOnly=false) {
    $ajax = false;
    if (substr($controller,0,5)=='/ajax') {
    	$controller = substr($controller,5);
    	$ajax = true;
    }
		$controller = DevmoCore::getObject(
			DevmoCore::getFile('controllers',$controller.'Controller'),
			'Controller',
			($loadOnly?'load':'new'));
		if ($loadOnly)
			return true;
    $controller->setAjax($ajax);
    return $controller;
  }

	
  public static function getView ($template, $tokens=null) {
  	$view = new View();
		$view->setTemplate(DevmoCore::getFile('views',$template)->file);
  	if (is_object($tokens) || is_array($tokens))
  		$view->setTokens($tokens);
  	return $view;
  }


  public static function getDao ($dao, $loadOnly=false) {
		return DevmoCore::getObject(
			DevmoCore::getFile('daos',$dao.'Dao'),
			'Dao',
			($loadOnly?'load':'auto'));
  }


  public static function getLibrary ($class, $option='auto') {
    return DevmoCore::getObject(
    	DevmoCore::getFile('libraries',$class),
    	null,
    	$option);
  }


	public static function debug ($obj, $text='DEBUG', $opt=FALSE) {
	  echo "<pre>\n";
	  echo "{$text}\n";
	  switch ($opt) {
	    default:
				print_r($obj);
				break;
	    case 'fatal':
				print_r($obj);
				exit;
				break;
	    case 'trace':
				debug_print_backtrace();
				print_r($obj);
				break;
	    case 'obj':
				print_r($obj);
				break;
	    case 'xml':
				echo $obj->asXML();
				break;
	  }
	  echo "\n</pre>";
	}

}

// check for magic quotes
if (get_magic_quotes_gpc())
  die("Magic Quotes Config is On.  Website now Exiting.");
// path checks
if (!defined('DEVMO_DIR'))
	throw new Exception('Missing Constant DEVMO_DIR');
if (!is_dir(DEVMO_DIR))
	throw new Exception('Invalid DEVMO_DIR ['.DEVMO_DIR.']');
// require core classes
require(DEVMO_DIR."/library/DevmoCore.php");
require(DEVMO_DIR."/library/DevmoException.php");
require(DEVMO_DIR."/library/Logger.php");
require(DEVMO_DIR."/library/View.php");
require(DEVMO_DIR."/library/Loader.php");
require(DEVMO_DIR."/controllers/Controller.php");
require(DEVMO_DIR."/library/Deprecated.php");
// set default exception handler
set_exception_handler(array('DevmoCore','handleException'));
// sanitize data
DevmoCore::sanitize($_GET);
DevmoCore::sanitize($_POST);
DevmoCore::sanitize($_REQUEST);
// set defaults
Devmo::setDebug(false);
Devmo::setLog('../log/'.strtolower(Devmo::getServer('HTTP_HOST')).'.log');
Devmo::setAppPath('../app');
Devmo::setRequestedController(Devmo::getServer('PATH_INFO'));
Devmo::setHomeController('/Home');
