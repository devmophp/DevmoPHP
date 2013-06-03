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
	public static function run () {
		Config::sortNamespacePathMap();
		return Core::execute()->getRoot();
	}
}
// set default configs
Config::init();
Config::addNamespacePathMapping('devmo',DEVMO_PATH,false);
Config::setRequestBase(Devmo::getValue('SCRIPT_NAME',$_SERVER));
Config::setRequest(Devmo::getValue('PATH_INFO',$_SERVER));
