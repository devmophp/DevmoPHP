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
			echo \Devmo\libs\Core::execute()->getRoot();
		} catch (\Devmo\libs\CoreException $e) {
			if (\Devmo\libs\Core::$debug) {
				$controller = self::getObject('Devmo.controllers.Error');
				$controller->template = $e->controller;
				$controller->setData($e->tokens);
				echo $controller->run();
			} else {
				header("HTTP/1.0 404 Not Found");
				echo \Devmo\libs\Core::execute('/FourOFour')->getRoot();
			}
		}
	}


	public static function setAppPath ($path) {
		foreach (\Devmo\libs\Core::$paths as $k=>$v) {
			\Devmo\libs\Core::$paths[$k] = array($path);
		}
	}


	public static function addAppPath ($path) {
		foreach (\Devmo\libs\Core::$paths as $k=>$v) {
			\Devmo\libs\Core::$paths[$k][] = $path;
		}
	}


	public static function setAppNamespace ($namespace) {
		\Devmo\libs\Core::$namespace = $namespace;
	}


	public static function addControllerPath ($path) {
		\Devmo\libs\Core::$paths['controllers'][] = $path;
	}


	public static function addViewPath ($path) {
		\Devmo\libs\Core::$paths['views'][] = $path;
	}


	public static function addLibPath ($path) {
		\Devmo\libs\Core::$paths['libs'][] = $path;
	}


	public static function addDaoPath ($path) {
		\Devmo\libs\Core::$paths['daos'][] = $path;
	}


	public static function addMapping ($mapping) {
		\Devmo\libs\Core::$mappings[] = $mapping;
	}


	public static function setDebug ($debug=false) {
		\Devmo\libs\Core::$debug = ($debug==true);
	}


	public static function setLog ($file) {
		\Devmo\libs\Logger::setDefaultFile($file);
	}


	public static function setHomeController ($controller) {
		\Devmo\libs\Core::$homeController = $controller;
	}


	public static function setRequestedController ($controller) {
		\Devmo\libs\Core::$requestedController = $controller;
	}


	public static function getValue ($name, $mixed=null) {
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
		return \Devmo\libs\Core::$debug;
	}


	public static function getObject ($class, $option='auto') {
		return \Devmo\libs\Core::getObject($class,$option);
	}


	public static function loadObject ($class) {
		return \Devmo\libs\Core::getObject($class,'load');
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
require(DEVMO_DIR."/libs/Exception.php");
require(DEVMO_DIR."/libs/Core.php");
require(DEVMO_DIR."/libs/Deprecated.php");
// set defaults
Devmo::setDebug(false);
Devmo::setLog('../log/'.strtolower(Devmo::getServer('HTTP_HOST')).'.log');
Devmo::setAppPath('../app');
Devmo::setRequestedController(Devmo::getServer('PATH_INFO'));
Devmo::setHomeController('/Home');
