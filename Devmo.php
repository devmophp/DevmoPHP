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
      echo \Devmo\Core::execute()->getRoot();
    } catch (\Devmo\CoreException $e) {
    	if (\Devmo\Core::$debug) {
    		$controller = self::getController('/Error');
    		$controller->template = $e->controller;
    		$controller->setData($e->tokens);
      	echo $controller->run();
			} else {
		  	header("HTTP/1.0 404 Not Found");
      	echo \Devmo\Core::execute('/FourOFour')->getRoot();
			}
    }
  }


	public static function setAppPath ($path) {
		foreach (\Devmo\Core::$paths as $k=>$v) {
			\Devmo\Core::$paths[$k] = array($path);
		}
	}


	public static function addAppPath ($path) {
		foreach (\Devmo\Core::$paths as $k=>$v) {
			\Devmo\Core::$paths[$k][] = $path;
		}
	}
	
	
	public static function setAppNamespace ($namespace) {
		\Devmo\Core::$namespace = $namespace;
	}


	public static function addControllerPath ($path) {
		\Devmo\Core::$paths['controllers'][] = $path;
	}


	public static function addViewPath ($path) {
		\Devmo\Core::$paths['views'][] = $path;
	}


	public static function addLibraryPath ($path) {
		\Devmo\Core::$paths['libraries'][] = $path;
	}


	public static function addDaoPath ($path) {
		\Devmo\Core::$paths['daos'][] = $path;
	}


	public static function addMapping ($mapping) {
		\Devmo\Core::$mappings[] = $mapping;
	}


	public static function setDebug ($debug=false) {
		\Devmo\Core::$debug = ($debug==true);
	}


	public static function setLog ($file) {
		\Devmo\Logger::setDefaultFile($file);
	}


	public static function setHomeController ($controller) {
		\Devmo\Core::$homeController = $controller;
	}


	public static function setRequestedController ($controller) {
		\Devmo\Core::$requestedController = $controller;
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
  	return \Devmo\Core::$debug;
  }


  public static function getController ($controller, $loadOnly=false, $fromAutoloader=false) {
    $ajax = false;
    if (substr($controller,0,5)=='/ajax') {
    	$controller = substr($controller,5);
    	$ajax = true;
    }
		$controller = $fromAutoloader
			? preg_replace('=/controllers/([^/]+)$=','/\1',$controller)
			: preg_replace('=/([^/]+)$=','/\1Controller',$controller);
		$controller = \Devmo\Core::getObject(
			\Devmo\Core::getFile('controllers',$controller),
			'\Devmo\controllers\Controller',
			($loadOnly?'load':'new'));
		if ($loadOnly)
			return true;
    $controller->setAjax($ajax);
    return $controller;
  }

	
  public static function getView ($template, $tokens=null) {
  	$view = new \Devmo\View();
		$view->setTemplate(\Devmo\Core::getFile('views',$template)->file);
  	if (is_object($tokens) || is_array($tokens))
  		$view->setTokens($tokens);
  	return $view;
  }


  public static function getDao ($dao, $loadOnly=false) {
		return \Devmo\Core::getObject(
			\Devmo\Core::getFile('daos',$dao.'Dao'),
			'\Devmo\Dao',
			($loadOnly?'load':'auto'));
  }


  public static function getDto ($dto, $loadOnly=false) {
		return \Devmo\Core::getObject(
			\Devmo\Core::getFile('dtos',$dto.'Dto'),
			'\Devmo\Dto',
			($loadOnly?'load':'auto'));
  }


  public static function getLibrary ($class, $option='auto') {
    return \Devmo\Core::getObject(
    	\Devmo\Core::getFile('libraries',$class),
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

// require core classes
define('DEVMO_DIR',preg_replace('=^(.+)/[^/]+$=','\1',__FILE__));
require(DEVMO_DIR."/libraries/Exception.php");
require(DEVMO_DIR."/libraries/Core.php");
require(DEVMO_DIR."/libraries/Deprecated.php");
// set defaults
Devmo::setDebug(false);
Devmo::setLog('../log/'.strtolower(Devmo::getServer('HTTP_HOST')).'.log');
Devmo::setAppPath('../app');
Devmo::setRequestedController(Devmo::getServer('PATH_INFO'));
Devmo::setHomeController('/Home');
