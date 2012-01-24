<?php
// set framework constants
if (!defined('DEVMO_PATH'))
	define('DEVMO_PATH',preg_replace('=^(.+)/[^/]+$=','\1',__FILE__));
// require core classes
require(DEVMO_PATH."/libs/Core.php");

// uses
use \devmo\libs\Core;
use \devmo\libs\Config;

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
		echo '<pre>'.PHP_EOL;
		echo $text.PHP_EOL;
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
		echo PHP_EOL.'</pre>';
	}
	
}

// set default configs
Config::addNamespacePathMapping('devmo',DEVMO_PATH,false);
Config::setRequest(Devmo::getValue('PATH_INFO',$_SERVER));
