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


  public static function run ($controller=null) {
    try {
			Path::init();
      echo Manager::execute($controller)->getRoot();
    } catch (CoreError $e) {
    	if (DEVMO_DEV) {
    		$controller = Factory::getController('/Error');
    		$controller->template = $e->controller;
    		$controller->setData($e->tokens);
      	echo $controller->run();
			} else {
				if ($controller=='/FourOFour')
					throw new Exception('Problem with 404: '.$e->getMessage());
		  	header("HTTP/1.0 404 Not Found");
				echo self::run('/FourOFour');
			}
    }
  }




  /**
   * Cleans an associative array from harmful characters.
   * mostly used for http request arrays
   *
   * @param associative array
   * @return none
   */
  public static function sanitize (&$hash) {
    foreach ($hash as $k=>$v)
      is_array($v)
        ? self::sanitize($v)
        : $hash[$k] = htmlentities(trim($v),ENT_NOQUOTES);
  }

}  // EOC





/**
 * Global convenient method used for debuging by outputting text
 *
 * @param Object, header text, option (fatal, trace, obj, xml)
 * @return void
 */
function debug ($obj, $text='DEBUG', $opt=FALSE) {
  echo "<pre>\n";
  echo "{$text}\n";
  switch ($opt) {
    default:      print_r($obj);           break;
    case 'fatal': print_r($obj); exit;     break;
    case 'trace': debug_print_backtrace(); print_r($obj); break;
    case 'obj':   var_dump($obj);          break;
    case 'xml':   echo $obj->asXML();      break;
  }
  echo "\n</pre>";
}




/**
 * Global exception handler for the framework
 *
 * @param Exception
 * @return void
 */
function exceptionCatcher (Exception $e) {
  DEVMO_DEV
  	? debug($e->__toString(),'log entry')
		: debug($e->getMessage(),'See the error log for more details');
  if (class_exists('Log') && !Log::add(DEVMO_LOG_FILE,$e->__toString()))
    debug(null,'Could not log error');
}




/**
 * Provides a global and secure container
 * for class wide variables with unique keys.
 *
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @category Utility
 * @version 1.0
 */
class Globals {
  private static $vars = array();




  /**
   * Returns a global variable
   *
   * @param name of variable
   * @return value
   */
  public static function get ($key) {
    return $key
      ? Util::getValue($key,self::$vars)
      : self::$vars;
  }




  /**
   * Sets a global variable
   *
   * @param name of variable, value of variable
   * @return none
   */
  public static function set ($key, $val=null) {
    if (!$key)
      throw new Error("Missing variable name");
    self::$vars[$key] = $val;
  }

}




/**
 * Provides a global and secure container
 * for form generated errors
 *
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @category Utility
 * @version 1.0
 */
class Errors {
  private static $errors = array();




  /**
   * Adds an error
   *
   * @param name of variable
   * @return value
   */
  public static function add ($error=null) {
    if ($error!=null)
      self::$errors[] = $error;
  }




  /**
   * Retunrs an array of errors
   *
   * @param name of variable, value of variable
   * @return none
   */
  public static function get () {
    return self::$errors;
  }




  /**
   * Clears the errors
   *
   * @param name of variable, value of variable
   * @return none
   */
  public static function clear () {
    self::$errors = array();
  }

}

abstract class Dao {
}

/**
 * Let's do the heavy lifting now!
 */
//  check for magic quotes
if (get_magic_quotes_gpc())
  die("Magic Quotes Config is On.  Website now Exiting.");
// set the default timezone to use. Available since PHP 5.1
if (defined('DEVMO_TIMEZONE'))
	date_default_timezone_set(DEVMO_TIMEZONE);
// set dev switch
if (!defined('DEVMO_DEV'))
	define('DEVMO_DEV',false);
//  set default exception handler
set_exception_handler('exceptionCatcher');
//	path checks
if (!defined('DEVMO_DIR'))
	throw new Exception('Missing Constant DEVMO_DIR');
if (!is_dir(DEVMO_DIR))
	throw new Exception('Invalid DEVMO_DIR ['.DEVMO_DIR.']');
if (!defined('DEVMO_APP_DIR'))
	throw new Exception('Missing Constant DEVMO_APP_DIR');
if (!is_dir(DEVMO_APP_DIR))
	throw new Exception('Invalid DEVMO_APP_DIR ['.DEVMO_APP_DIR.']');
if (!defined('DEVMO_VIEW_DIR'))
	define('DEVMO_VIEW_DIR',DEVMO_APP_DIR);
if (!is_dir(DEVMO_VIEW_DIR))
	throw new Exception('Invalid DEVMO_VIEW_DIR ['.DEVMO_VIEW_DIR.']');
if (!defined('DEVMO_LOG_DIR'))
	throw new Exception('Missing Constant DEVMO_LOG_DIR');
if (!is_dir(DEVMO_LOG_DIR))
	throw new Exception('Invalid DEVMO_LOG_DIR ['.DEVMO_LOG_DIR.']');
if (!defined('DEVMO_LOG_FILE'))
	throw new Exception('Missing Constant DEVMO_LOG_FILE');
if (!defined('DEVMO_REQUESTED_CONTROLLER') && !empty($_SERVER['PATH_INFO']))
	define('DEVMO_REQUESTED_CONTROLLER',$_SERVER['PATH_INFO']);
//  Framework initial file requirements
require(DEVMO_DIR."/library/Util.php");
require(DEVMO_DIR."/errors/Error.php");
require(DEVMO_DIR."/library/Path.php");
require(DEVMO_DIR."/library/Factory.php");
require(DEVMO_DIR."/library/Log.php");
require(DEVMO_DIR."/library/Manager.php");
require(DEVMO_DIR."/library/Loader.php");
require(DEVMO_DIR."/controllers/Controller.php");
require(DEVMO_DIR."/library/View.php");
//  sanitize data coming in.
Devmo::sanitize($_GET);
Devmo::sanitize($_POST);
Devmo::sanitize($_REQUEST);
