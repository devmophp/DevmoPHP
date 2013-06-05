<?php
// set framework constants
if (!defined('DEVMO_PATH'))
	define('DEVMO_PATH',preg_replace('=^(.+)/[^/]+$=','\1',__FILE__));
// require core classes
require_once DEVMO_PATH.'/devmo/Core.php';
// uses
use \devmo\Core;
use \devmo\Config;
// class
class Devmo extends \devmo\Object {
	private static $init = false;
	private static function init () {
		Config::sortNamespacePathMap();
		self::$init = true;
	}
	public static function run ($controller=null, $args=null) {
		if (!self::$init)
			self::init();
		return Core::execute(($controller ? Core::formatPath($controller,'controllers') : null),$args)->getRoot();
	}
}
// set default configs
Config::init();
Config::addNamespacePathMapping('devmo',DEVMO_PATH,false);
Config::setRequestBase(Devmo::getValue('SCRIPT_NAME',$_SERVER));
Config::setRequest(Devmo::getValue('PATH_INFO',$_SERVER));
