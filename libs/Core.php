<?php
namespace devmo\libs;

use \Devmo;
use \devmo\exceptions\Exception;
use \devmo\exceptions\CoreException;
use \devmo\exceptions\InvalidException;

class Core {

	public static function execute ($path=false, $data=null) {
		// find controller
		if (!($controller = $path) && Config::getRequestedController()) {
			$controller = Config::getRequestedController();
			// find mapped controller
			if (Config::hasRequestControllerMap()) {
				foreach (Config::getRequestControllerMap() as $pattern=>$useController) {
					if (preg_match($pattern,$controller)) {
						$controller  = $useController;
						break;
					}
				}
			}
		}
		if (!$controller || $controller === '/') {
			$controller = Config::getDefaultController();
		}
		//	get controller view
		if (!$view = self::executeController($controller,$data))
			throw new CoreException('ViewNotFound',array('controller'=>$controller));
		return $view;
	}


	private static function executeController ($path, $data=null, $message=null) {
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
				throw new Exception('Success Controller Can Not Equal Self ['.$path.']');
			return self::executeController($controller->getSuccess(),null,$controller->getMessage());
			//  unsuccessful execution
		} else if ($view===false && $controller->getFailure()) {
			if ($controller->getFailure()==$path)
				throw new Exception('Failure Controller Can Not Equal Self ['.$path.']');
			return self::executeController($controller->getFailure(),null,$controller->getMessage());
		} else if ($view instanceof View) {
			return $view;
		}
	}


	public static function getFileBox ($path) {
		// get directory map
		$typeDirectoryMap = Config::getTypeDirectoryMap();
		$typeNamespacePathMap = Config::getTypeNamespacePathMap();
		// find context and type
		preg_match('/^(.*?)([^\.]+)\.([^\.]+)$/',$path,$matches);
		$context = Devmo::getValue(1,$matches);
		if (!isset($matches[2]) || !isset($typeDirectoryMap[$matches[2]]))
			throw new CoreException('FileTypeNotFound',array('type'=>Devmo::getValue(2,$matches),'path'=>$path,'types'=>implode(',',array_keys($typeDirectoryMap))));
		$type = $matches[2];
		// put it together
		$xFile = $file = $class = null;
		foreach ($typeNamespacePathMap[$type] as $xNamespace=>$xPath) {
			if (preg_match("/^{$xNamespace}/",$context)>0) {
				$xName = preg_replace('/[ _-]+/','',ucwords($matches[3]));
				$xDir = preg_replace("/^{$xNamespace}/",$xPath,str_replace('.','/',$context)).($xNamespace=='devmo'?$type:$typeDirectoryMap[$type]);
				$xFile = $xDir.'/'.$xName.'.php';
				if (is_file($xFile)) {
					$file = $xFile;
					$class = str_replace('.','\\','\\'.$context.$type.'\\'.$xName);
					break;
				}
			}
		}
		if ($xFile==null)
			throw new CoreException('NamespaceNotDefined',array('path'=>$path,'context'=>$context,'namespaces'=>implode(',',array_keys($typeNamespacePathMap[$type]))));
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
		if ($e instanceof CoreException) {
			echo self::handleCoreException($e);
		} else {
			Config::getDebug()
				? Devmo::debug($e->__toString(),'log entry')
				: Devmo::debug($e->getMessage(),'See the error log for more details');
			self::logException($e);
		}
	}


	public static function handleCoreException (CoreException $e) {
		if (Config::getDebug()) {
			$controller = self::getObject('devmo.controllers.Error','new');
			$controller->setException($e);
			$controller->setData($e->tokens);
			return $controller->run();
		} else {
			return self::execute(Config::getPageNotFoundController())->getRoot();
		}
	}


	public static function loadClass ($class) {
		if (strstr($class,'\\'))
			$class = str_replace(array('/','\\'),'.',$class);
		self::getObject($class,'load');
	}

	public static function logException (\Exception $e) {
		($logFile = Config::getErrorLog())
			? error_log($e->__toString(),3,$logFile)
			: error_log(preg_replace('=[\t'.PHP_EOL.']+=',' ',$e->__toString()),0);
	}


	public static function formatPath ($path, $type, $context=null) {
		if ($context && !strstr($path,'.'))
			$path = $context.$path;
		return !strstr($path,".{$type}.")
			? preg_replace('=(.*?)([a-zA-Z0-9]+)$=','\1'.$type.'.\2',$path)
			: $path;
	}


	public static function formatRequestToPath ($request) {
		preg_match('=(.*?)/?([^/]+$)=',$request,$matches);
		return str_replace('/','.',$matches[1].'.controllers.'.str_replace(' ','',ucwords(preg_replace('/[\-\+]+/',' ',$matches[2]))));
	}

}




# pragma mark Config
class Config {
	private static $typeDirectoryMap = array(
			'controllers'=>'_controllers',
			'views'=>'_views',
			'libs'=>'_libs',
			'exceptions'=>'_exceptions',
			'includes'=>'_incs',
			'daos'=>'_daos',
			'dtos'=>'_dtos');
	private static $typeNamespacePathMap = array(
			'controllers'=>array(),
			'views'=>array(),
			'libs'=>array(),
			'exceptions'=>array(),
			'includes'=>array(),
			'daos'=>array(),
			'dtos'=>array());
	private static $requestControllerMap = array();
	private static $pageNotFoundController = 'devmo.controllers.FourOFour';
	private static $requestedController = null;
	private static $defaultController = null;
	private static $defaultNamespace = null;
	private static $debug = false;
	private static $errorLogFile = null;
	private static $request = null;


	# for application use
	public static function addNamespacePathMapping ($namespace, $path, $default=false) {
		foreach (self::$typeNamespacePathMap as $k=>$v)
			self::$typeNamespacePathMap[$k][$namespace] = $path;
		if ($default || (self::$defaultNamespace==null && $namespace!='devmo'))
			self::$defaultNamespace = $namespace;
	}

	public static function addRequestControllerMapping ($pattern, $controller) {
		self::$requestControllerMap[$pattern] = $controller;
	}

	public static function setDefaultNamespace ($namespace) {
		self::$defaultNamespace = $namespace;
	}

	public static function setDefaultController ($controller) {
		self::$defaultController = Core::formatPath($controller,'controllers');
	}

	public static function setPageNotFoundController ($controller) {
		self::$pageNotFoundController = Core::formatPath($controller,'controllers');
	}

	public static function setRequest ($request=null) {
		if ($request && $request!='/') {
			self::$request = $request;
			self::$requestedController = Core::formatRequestToPath($request);
		}
	}

	public static function setDebug ($debug=false) {
		self::$debug = ($debug);
	}

	public static function setErrorLog ($file) {
		self::$errorLogFile = $file;
	}


	# for framework use
	public static function getRequest () {
		return self::$request;
	}
	public static function getRequestedController () {
		return self::$requestedController ? self::getDefaultNamespace().self::$requestedController : null;
	}
	public static function getDefaultController () {
		return self::$defaultController;
	}
	public static function getPageNotFoundController () {
		return self::$pageNotFoundController;
	}
	public static function hasRequestControllerMap () {
		return (self::$requestControllerMap);
	}
	public static function getRequestControllerMap () {
		return self::$requestControllerMap;
	}
	public static function getTypeDirectoryMap () {
		return self::$typeDirectoryMap;
	}
	public static function getTypeNamespacePathMap () {
		return self::$typeNamespacePathMap;
	}
	public static function getErrorLog () {
		return self::$errorLogFile;
	}
	public static function getDebug () {
		return self::$debug;
	}
	public static function getDefaultNamespace () {
		if (!self::$defaultNamespace)
			throw new CoreException('DefaultNamespaceNotDefined');
		return self::$defaultNamespace;
	}
}



# pragma mark Box
class Box {
	public function __set ($name, $value) {
		$setter = 'set'.ucfirst($name);
    return $name && is_callable(array($this,$setter))
			? $this->$setter($name,$value)
			: $this->{$name} = $value;
	}
	public function __get ($name) {
		$getter = 'get'.ucfirst($name);
    return $name && is_callable(array($this,$getter))
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
