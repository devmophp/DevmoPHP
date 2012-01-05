<?php
// require core classes
define('DEVMO_DIR',preg_replace('=^(.+)/[^/]+$=','\1',__FILE__));
require(DEVMO_DIR."/libs/Core.php");
require(DEVMO_DIR."/libs/Exception.php");

use \devmo\libs\Core;
use \devmo\libs\Logger;
use \devmo\libs\CoreException;

class Devmo {


	public static function run () {
		try {
			return Core::execute()->getRoot();
		} catch (CoreException $e) {
			return Core::handleCoreException($e);
		}
	}


	public static function addNamespace ($namespace, $path, $default=false) {
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
		Core::$pageNotFoundController = Core::formatPath($controller,'controllers');
	}


	public static function setRequest ($request=null) {
		if ($request && $request!='/')
			Core::$requestedController = Core::formatRequestToPath($request);
	}


	public static function isDebug() {
		return Core::$debug;
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
Devmo::addNamespace('devmo',DEVMO_DIR);
Devmo::setDebug(false);
Devmo::setLog('../log/'.strtolower(Core::getValue('HTTP_HOST',$_SERVER)).'.log');
Devmo::setRequest(Core::getValue('PATH_INFO',$_SERVER));
