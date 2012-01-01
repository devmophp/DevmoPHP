<?php
// require core classes
define('DEVMO_DIR',preg_replace('=^(.+)/[^/]+$=','\1',__FILE__));
require(DEVMO_DIR."/libs/Core.php");
require(DEVMO_DIR."/libs/Exception.php");

use \Devmo\libs\Core;
use \Devmo\libs\Logger;
use \Devmo\libs\CoreException;

class Devmo {
	private static $pageNotFoundController = 'Devmo.controllers.FourOFour';


	public static function run () {
		try {
			return Core::execute()->getRoot();
		} catch (CoreException $e) {
			return Core::handleCoreException($e,self::$pageNotFoundController);
		}
	}


	public static function addAppPath ($namespace, $path, $default=false) {
		foreach (Core::$namespaces as $k=>$v)
			Core::$namespaces[$k][$namespace] = $path;
		if ($default || Core::$namespace==null)
			Core::$namespace = $namespace;
	}


	public static function addControllerPath ($namespace, $path) {
		Core::$paths['controllers'][$namespace] = $path;
	}


	public static function addViewPath ($namespace, $path) {
		Core::$paths['views'][$namespace] = $path;
	}


	public static function addLibPath ($namespace, $path) {
		Core::$paths['libs'][$namespace] = $path;
	}


	public static function addDaoPath ($namespace, $path) {
		Core::$paths['daos'][$namespace] = $path;
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
		Core::$homeController = Core::formatPath($controller,'controllers');
	}


	public static function setPageNotFoundController ($controller) {
		self::$pageNotFoundController = Core::formatPath($controller,'controllers');
	}


	public static function setRequestedController ($controller=null) {
		if ($controller && $controller!='/')
			Core::$requestedController = Core::$namespace.preg_replace(array('=/=','=\.([^\.]+)$='),array('.','.controllers.\1'),$controller);
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


	public static function getGet ($name, $makeSafe=true) {
		return (($value = self::getValue($name,$_GET)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}


	public static function getPost ($name) {
		return (($value = self::getValue($name,$_POST)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}


	public static function getRequest ($name) {
		return (($value = self::getValue($name,$_REQUEST)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}


	public static function getServer ($name) {
		return self::getValue($name,$_SERVER);
	}


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
Devmo::addAppPath('Devmo',DEVMO_DIR);
Devmo::setDebug(false);
Devmo::setLog('../log/'.strtolower(Devmo::getServer('HTTP_HOST')).'.log');
Devmo::setRequestedController(Devmo::getServer('PATH_INFO'));
