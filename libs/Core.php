<?php
namespace Devmo\libs;

use \Devmo\libs\CoreException;
use \Devmo\libs\InvalidException;
use \Devmo;

class Core {
	public static $debug = false;
	public static $paths = array('controllers'=>array(),'views'=>array(),'libs'=>array(),'daos'=>array(),'dtos'=>array());
	public static $folders = array('controllers'=>'_controllers','views'=>'_views','libs'=>'_libs','daos'=>'_daos','dtos'=>'_dtos');
	public static $mappings = array();
	public static $homeController = null;
	public static $requestedController = null;
	public static $namespace = null;


	public static function execute ($name=false, $data=null) {
		// find controller
		if (!($controller = $name) && self::$requestedController) {
			$controller = self::$requestedController;
		}
		if (!$controller || $controller === '/') {
			$controller = self::$homeController;
		}
		//	get controller view
		if (!$view = self::executeController($controller,$data))
			throw new CoreException('ViewNotFound',array('controller'=>'?'));
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
		$controller = self::getObject($path,'new');
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


	public static function getFileBox ($name) {
		preg_match('/^(.*?)([^\.]+)\.([^\.]+)$/',$name,$matches);
		// find context
		$context = null;
		if (!empty($matches[1]))
			$context = substr($matches[1],0,-1).'.';
		// define type
		if (!isset($matches[2]) || !isset(self::$folders[$matches[2]]))
			throw new \Devmo\libs\Exception("Unknown File Type:".\Devmo::getValue(2,$matches));
		$type = $matches[2];
		// format name
		$name = preg_replace('/[ _-]+/','',ucwords($matches[3]));
		// put it together
		$subPath = str_replace('.','/','/'.$context.self::$folders[$type]).'/'.$name.'.php';
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
			if (is_file($devmoFile)) {
				$file = $devmoFile;
			} else {
				throw new CoreException('/FileNotFound',array('file'=>$file));
			}
		}
		// return file box
		$box = new Box;
		$box->type = $type;
		$box->class = ($devmoFile
			? '\Devmo\\'.$type.'\\'.$name
			: str_replace('.','\\',self::$namespace.'\\'.$context.$type.'\\'.$name));
		$box->file = $file;
		$box->context = $context;
		return $box;
	}


	public static function getObject ($path, $option='auto') {
		$file = self::getFileBox($path);
		require_once($file->file);
		if ($option=='load')
			return true;
		//  check for class
		$class = $file->class;
		// load file
		if (!class_exists($class))
			throw new CoreException('ClassNotFound',array('class'=>$class,'file'=>$file->file));
		//  check for parent class
		$parentClass = null;
		switch ($file->type) {
			case 'controllers': $parentClass = '\Devmo\controllers\Controller'; break;
			case 'views': $parentClass = '\Devmo\libs\View'; break;
			case 'daos': $parentClass = '\Devmo\daos\Dao'; break;
			case 'dtos': $parentClass = '\Devmo\dtos\Dto'; break;
		}
		if ($parentClass && !class_exists($parentClass))
			throw new CoreException('ClassNotFound',array('class'=>$parentClass,'file'=>$file->file));
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
			throw new CoreException('ClassNotController',array('class'=>$file->getClass(),'file'=>$file->getFile()));
		if (($obj instanceof Loader))
			$obj->setFileBox($file);
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
			? Devmo::debug($e->__toString(),'log entry')
			: Devmo::debug($e->getMessage(),'See the error log for more details');
		if (!Logger::add($e->__toString()))
			Devmo::debug(null,'Could not log error');
	}

	public static function loadClass ($class) {
		if (strstr($class,'\\'))
			$class = str_replace(array('/','\\'),'.',$class);
		self::getObject($class,'load');
	}

}


class Box {
	private $devmoBoxData = array();

	public function __set ($name, $value) {
		if (empty($name))
			throw new InvalidException('Data Key',$name);
		$this->devmoBoxData[$name] = $value;
	}

	public function __get ($name) {
		return Devmo::getValue($name,$this->devmoBoxData);
	}
}

// check for magic quotes
if (get_magic_quotes_gpc())
	die("Magic Quotes Config is On... exiting.");
// set default exception handler
set_exception_handler(array('\Devmo\libs\Core','handleException'));
spl_autoload_register(array('\Devmo\libs\Core','loadClass'));
// sanitize data
Core::sanitize($_GET);
Core::sanitize($_POST);
Core::sanitize($_REQUEST);
