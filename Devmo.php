<?php
// require core classes
define('DEVMO_DIR',preg_replace('=^(.+)/[^/]+$=','\1',__FILE__));
require(DEVMO_DIR."/libs/Core.php");
require(DEVMO_DIR."/libs/Exception.php");
require(DEVMO_DIR."/libs/Deprecated.php");

use \Devmo\libs\Core;
use \Devmo\libs\Logger;
use \Devmo\libs\CoreException;

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
			echo Core::execute()->getRoot();
		} catch (CoreException $e) {
			if (Core::$debug) {
				$controller = self::getObject('Devmo.controllers.Error');
				$controller->template = $e->controller;
				$controller->setData($e->tokens);
				echo $controller->run();
			} else {
				echo Core::execute('/FourOFour')->getRoot();
			}
		}
	}


	public static function setAppPath ($path) {
		foreach (Core::$paths as $k=>$v) {
			Core::$paths[$k] = array($path);
		}
	}


	public static function addAppPath ($path) {
		foreach (Core::$paths as $k=>$v) {
			Core::$paths[$k][] = $path;
		}
	}


	public static function setAppNamespace ($namespace) {
		Core::$namespace = $namespace;
	}


	public static function addControllerPath ($path) {
		Core::$paths['controllers'][] = $path;
	}


	public static function addViewPath ($path) {
		Core::$paths['views'][] = $path;
	}


	public static function addLibPath ($path) {
		Core::$paths['libs'][] = $path;
	}


	public static function addDaoPath ($path) {
		Core::$paths['daos'][] = $path;
	}


	public static function addMapping ($mapping) {
		Core::$mappings[] = $mapping;
	}


	public static function setDebug ($debug=false) {
		Core::$debug = ($debug==true);
	}


	public static function setLog ($file) {
		Logger::setDefaultFile($file);
	}


	public static function setHomeController ($controller) {
		Core::$homeController = $controller;
	}


	public static function setRequestedController ($controller) {
		Core::$requestedController = $controller;
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
		return Core::$debug;
	}


	public static function getObject ($class, $option='auto') {
		return Core::getObject($class,$option);
	}


	public static function loadObject ($class) {
		return Core::getObject($class,'load');
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

// set defaults
Devmo::setDebug(false);
Devmo::setLog('../log/'.strtolower(Devmo::getServer('HTTP_HOST')).'.log');
Devmo::setAppPath('../app');
Devmo::setRequestedController(Devmo::getServer('PATH_INFO'));
Devmo::setHomeController('/Home');
