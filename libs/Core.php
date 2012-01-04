<?php
namespace devmo\libs;

use \devmo\libs\CoreException;
use \devmo\libs\InvalidException;
use \devmo;

class Core {
	public static $debug = false;
	public static $namespace = null;
	public static $namespaces = array('controllers'=>array(),'views'=>array(),'libs'=>array(),'daos'=>array(),'dtos'=>array());
	public static $folders = array('controllers'=>'_controllers','views'=>'_views','libs'=>'_libs','daos'=>'_daos','dtos'=>'_dtos');
	public static $mappings = array();
	public static $homeController = null;
	public static $requestedController = null;


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
			throw new CoreException('ViewNotFound',array('controller'=>$controller));
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
		$context = Devmo::getValue(1,$matches);
		// define type
		if (!isset($matches[2]) || !isset(self::$folders[$matches[2]]))
			throw new \devmo\libs\Exception((($fileType = Devmo::getValue(2,$matches))?"unknown file type:{$fileType}":"missing file type")." for:{$name}"." (types:".implode(',',array_keys(self::$folders)).")");
		$type = $matches[2];
		// find it
		$xFile = $file = $class = null;
		// put it together
		foreach (self::$namespaces[$type] as $namespace=>$path) {
			if (preg_match("/^{$namespace}/",$context)>0) {
				$xName = preg_replace('/[ _-]+/','',ucwords($matches[3])); 
				$xFolder = ($namespace=='devmo'?$type:self::$folders[$type]);
				$xPath = preg_replace("/^{$namespace}/",$path,str_replace('.','/',$context));
				$xFile = $xPath.$xFolder.'/'.$xName.'.php';
				if (is_file($xFile)) {
					$file = $xFile;
					$class = str_replace('.','\\','\\'.$context.$type.'\\'.$xName);
					break;
				}
			}
		}
		if ($xFile==null)
			throw new CoreException('NamespaceNotDefined',array('name'=>$name,'namespaces'=>self::$namespaces[$type]));
		if ($file==null)
			throw new CoreException('FileNotFound',array('request'=>$xFile));
		// return file box
		$box = new Box;
		$box->type = $type;
		$box->class = $class;
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
			case 'controllers': $parentClass = '\devmo\controllers\Controller'; break;
			case 'views': $parentClass = '\devmo\libs\View'; break;
			case 'daos': $parentClass = '\devmo\daos\Dao'; break;
			case 'dtos': $parentClass = '\devmo\dtos\Dto'; break;
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
			throw new CoreException('ClassNotController',array('class'=>$file->class,'file'=>$file->file));
		if (($obj instanceof Loader))
			$obj->setFileBox($file);
		return $obj;
	}


	public static function makeSafe ($value) {
		if (is_array($value))
			self::makeSafeSub($value);
		return htmlentities(trim($value),ENT_NOQUOTES);
	}
	private static function makeSafeSub (&$hash) {
		foreach ($hash as $k=>$v)
			is_array($v)
				? self::makeSafeSub($v)
				: $hash[$k] = self::makeSafe($v);
	}


	public static function handleException (\Exception $e) {
		self::$debug
			? Devmo::debug($e->__toString(),'log entry')
			: Devmo::debug($e->getMessage(),'See the error log for more details');
		if (!Logger::add($e->__toString()))
			Devmo::debug(null,'Could not log error');
	}


	public static function handleCoreException (CoreException $e, $pageNotFoundController) {
		if (self::$debug) {
			$controller = self::getObject('devmo.controllers.Error','new');
			$controller->setException($e);
			$controller->setData($e->tokens);
			return $controller->run();
		} else {
			return self::execute($pageNotFoundController)->getRoot();
		}
	}
	
	
	public static function loadClass ($class) {
		if (strstr($class,'\\'))
			$class = str_replace(array('/','\\'),'.',$class);
		self::getObject($class,'load');
	}


	public static function formatPath ($path, $type, $context=null) {
		if ($context && !strstr($path,'.'))
			$path = $context.$path;
		return !strstr($path,".{$type}.")
			? preg_replace('=(.*?)([a-zA-Z0-9]+)$=','\1'.$type.'.\2',$path)
			: $path;
	}
	
}




class Box {

	public function __set ($name, $value) {
		$setter = 'set'.ucfirst($name);
    return $name && method_exists($this,$setter)
			? $this->$setter($name,$value)
			: $this->{$name} = $value;
	}


	public function __get ($name) {
		$getter = 'get'.ucfirst($name);
    return $name && method_exists($this,$getter)
			? $this->$getter()
			: Devmo::getValue($name,$this);
	}

}




// check for magic quotes
if (get_magic_quotes_gpc())
	die("Magic Quotes Config is On... exiting.");
// set default exception handler
set_exception_handler(array('\devmo\libs\Core','handleException'));
spl_autoload_register(array('\devmo\libs\Core','loadClass'));