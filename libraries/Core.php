<?php
namespace Devmo;

class Core {
	public static $debug = false;
	public static $paths = array('controllers'=>array(),'views'=>array(),'libraries'=>array(),'daos'=>array());
	public static $folders = array('controllers'=>'_controllers','views'=>'_views','libraries'=>'_libraries');
	public static $mappings = array();
	public static $homeController = null;
	public static $requestedController = null;
	public static $namespace = null;


  public static function execute ($name=false,$data=null) {
  	// find controller
  	if (!($controller = $name) && self::$requestedController) {
			$controller = self::$requestedController;
		}
		if (!$controller || $controller === '/') {
			$controller = self::$homeController;
		}
  	//	get controller view
    if (!$view = self::executeController($controller,$data))
	    throw new \Devmo\CoreException('ViewNotFound',array('controller'=>'?'));
    return $view;
  }


  private static function executeController ($path, $data=null, $message=null) {
		// find mapped controller
  	if (self::$mappings) {
			foreach (self::$mappings as $pattern=>$usePath) {
				if (preg_match($pattern,$path)) {
					$path = $usePath;
					break;
				}
			}
		}
    //  get controller object
		$controller = \Devmo::getController($path);
		if ($data)
			$controller->setData($data);
		if ($message)
    	$controller->setMessage($message);
		//	run controller
    $view = $controller->run();
    //  forward to next controller
    if ($controller->getForward())
      return self::executeController($controller->getForward(),$data,$message);
    //  successful execution go to next
    if ($view===true && $controller->getSuccess()) {
      if ($controller->getSuccess()==$path)
      	throw new DevmoException('Success Controller Can Not Equal Self ['.$path.']');
      return self::executeController($controller->getSuccess(),null,$controller->getMessage());
    //  unsuccessful execution
    } else if ($view===false && $controller->getFailure()) {
      if ($controller->getFailure()==$path)
      	throw new DevmoException('Failure Controller Can Not Equal Self ['.$path.']');
      return self::executeController($controller->getFailure(),null,$controller->getMessage());
    } else if ($view instanceof View) {
    	return $view;
    }
  }


	public static function getFile ($type, $name) {
		$ogname = $name;
		preg_match('/^[\/]*(.*?)([^\/]+)$/',$name,$matches);
		// find context
		if (empty($matches[1])) {
			$context = '/';
		} else {
			$context = substr('/'.$matches[1],0,-1).'/';
		}
		// format name
		$name = preg_replace('/[ _-]+/','',ucwords($matches[2]));
		// put it together
		$subPath = $context.self::$folders[$type].'/'.$name.'.php';
		// find it
		$file = null;
  	foreach (self::$paths[$type] as $path) {
  		$file = $path.$subPath;
			if (is_file($file)) {
				break;
			}
  	}
    //  check framwork core
    $devmoFile = null;
    if (!is_file($file)) {
    	$devmoFile = DEVMO_DIR.'/'.$type.'/'.$name.'.php';
			//\Devmo::debug($devmoFile,'Core::getFile('.$type.','.$ogname.')');
    	if (is_file($devmoFile)) {
		    $file = $devmoFile;
			} else { 
				\Devmo::debug($file,'Core::getFile('.$type.','.$ogname.')','trace');
	      throw new \Devmo\CoreException('/FileNotFound',array('file'=>$file));
			}
    }
		// return file box
		$box = new \Devmo\Box;
		$box->class = ($devmoFile ? '\Devmo\\'.$name : str_replace('/','\\',self::$namespace.$context.$type.'/'.$name));
		$box->file = $file;
		$box->context = $context;
		// \Devmo::debug($box,'Core::getFile('.$type.','.$ogname.')');
		return $box;
	} 
	

	public static function getObject (\Devmo\Box $file, $parentClass=null, $option='auto') {
		require_once($file->file);
		if ($option=='load')
			return true;
    //  check for class
    $class = $file->class;
		// load file
    if (!class_exists($class))
      throw new \Devmo\CoreException('ClassNotFound',array('class'=>$class,'file'=>$file->file));
    //  check for parent class
    if ($parentClass && !class_exists($parentClass))
      throw new \Devmo\CoreException('ClassNotFound',array('class'=>$parentClass,'file'=>$file->getFile()));
    //  handle options
    $obj = null;
    switch ($option) {
			default:
      case 'auto':
        $obj = in_array('getInstance',get_class_methods($class))
          ? call_user_func(array($class,'getInstance'))
          : new $class;
        break;
      case 'singleton':
        $obj = $class::getInstance();
        break;
      case 'new':
        $obj = new $class;
        break;
    }
		if ($parentClass && !($obj instanceof $parentClass))
      throw new \Devmo\CoreException('ClassNotController',array('class'=>$file->getClass(),'file'=>$file->getFile()));
    if (($obj instanceof Loader) && !$obj->getContext())
    	$obj->setContext($file->context);
    return $obj;
	}


  public static function sanitize (&$hash) {
    foreach ($hash as $k=>$v)
      is_array($v)
        ? self::sanitize($v)
        : $hash[$k] = htmlentities(trim($v),ENT_NOQUOTES);
  }


	public static function handleException (Exception $e) {
		self::$debug
			? \Devmo::debug($e->__toString(),'log entry')
			: \Devmo::debug($e->getMessage(),'See the error log for more details');
		if (!Logger::add($e->__toString()))
			\Devmo::debug(null,'Could not log error');
	}

	public static function loadClass ($class) {
		$ogclass = $class;
		if (strstr($class,'\\'))
			$class = '/'.str_replace('\\','/',$class);
		if (substr($class,-10)=='Controller') {
			\Devmo::getController(str_replace('controllers/','',$class),true,true);
		} else if (substr($class,-3)=='Dao') {
			\Devmo::getDao(substr($class,0,-3),true);
		} else {
			\Devmo::getLibrary($class,'load');
		}
	}

}


class Box {
	private $devmoBoxData = array();

  public function __set ($name, $value) {
		if (empty($name)) 
			throw new \Devmo\InvalidException('Data Key',$name);
		$this->devmoBoxData[$name] = $value;
  }

  public function __get ($name) {
  	return \Devmo::getValue($name,$this->devmoBoxData);
  }

}


abstract class Dao {
}

// check for magic quotes
if (get_magic_quotes_gpc())
  die("Magic Quotes Config is On... exiting.");
// path checks
if (!defined('DEVMO_DIR'))
	throw new Exception('Missing Constant DEVMO_DIR');
if (!is_dir(DEVMO_DIR))
	throw new Exception('Invalid DEVMO_DIR ['.DEVMO_DIR.']');
// set default exception handler
set_exception_handler(array('\Devmo\Core','handleException'));
spl_autoload_register(array('\Devmo\Core','loadClass'));
// sanitize data
\Devmo\Core::sanitize($_GET);
\Devmo\Core::sanitize($_POST);
\Devmo\Core::sanitize($_REQUEST);
