<?php
// set framework constants
if (!defined('DEVMO_PATH'))
	define('DEVMO_PATH',preg_replace('=^(.+)/[^/]+$=','\1',__FILE__));
// require core classes
require(DEVMO_PATH."/devmo/Core.php");

// uses
use \devmo\Core;
use \devmo\Config;

class Devmo {

	public static function run () {
		return Core::execute()->getRoot();
	}

	public static function getValue ($name, $mixed=null, $default=false) {
		if (is_array($mixed))
			return isset($mixed[$name])
				? $mixed[$name]
				: $default;
		if (is_object($mixed))
			return isset($mixed->{$name})
				? $mixed->{$name}
				: $default;
	}

	public static function debug ($obj, $text='DEBUG', $opt=FALSE) {
		print Config::isCli() ? null : '<pre>'.PHP_EOL;
		print PHP_EOL.$text.PHP_EOL;
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
		print Config::isCli() ? null : PHP_EOL.'</pre>';
	}

	public static function logError ($e) {
		($logFile = Config::getErrorLog())
			? error_log((string)$e,3,$logFile)
			: error_log((string)$e,0);
	}

}

// set default configs
Config::addNamespacePathMapping('devmo',DEVMO_PATH,false);
Config::setRequest(Devmo::getValue('PATH_INFO',$_SERVER));
