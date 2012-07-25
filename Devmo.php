<?php
// set framework constants
if (!defined('DEVMO_PATH'))
	define('DEVMO_PATH',preg_replace('=^(.+)/[^/]+$=','\1',__FILE__));
// require core classes
require(DEVMO_PATH."/devmo/Core.php");
// uses
use \devmo\Core;
use \devmo\Config;
// class
class Devmo extends \devmo\Object {
	public static function run () {
		return Core::execute()->getRoot();
	}
	public static function logError ($e) {
		($logFile = Config::getErrorLog())
			? error_log((string)$e,3,$logFile)
			: error_log((string)$e,0);
	}
}
// set default configs
Config::init();
Config::addNamespacePathMapping('devmo',DEVMO_PATH,false);
Config::setRequest(Devmo::getValue('PATH_INFO',$_SERVER));
