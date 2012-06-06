<?php
namespace devmo\libs;

use \Devmo;
use \devmo\exceptions\Exception;
use \devmo\exceptions\CoreException;
use \devmo\exceptions\InvalidException;


class Core {
	public static function execute ($path=false, $args=null) {
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
		if (!$view = self::executeController($controller,$args))
			throw new CoreException('ViewNotFound',array('controller'=>$controller));
		return $view;
	}

	private static function executeController ($path, $args=null, $message=null) {
		//  get controller object
		$controller = self::getObject($path,'new');
		if ($message)
			$controller->setMessage($message);
		//	run controller
		$view = $controller->run($args);
		//  forward to next controller
		if ($controller->getForward())
			return self::executeController($controller->getForward(),$args,$message);
		// return only views
		if ($view instanceof View)
			return $view;
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
		return new FileBox(compact('type','class','file','context'));
	}

	public static function getObject ($path, $option='auto') {
		$fileBox = self::getFileBox($path);
		require_once($fileBox->getFile());
		if ($option=='load')
			return true;
		// check for class
		$class = $fileBox->getClass();
		// load file
		if (!class_exists($class))
			throw new CoreException('ClassNotFound',array('class'=>$class,'file'=>$fileBox->getFile()));
		// check for parent class
		$parentClass = null;
		switch ($fileBox->getType()) {
			case 'controllers': $parentClass = '\devmo\controllers\Controller'; break;
			case 'views': $parentClass = '\devmo\libs\View'; break;
			case 'daos': $parentClass = '\devmo\daos\Dao'; break;
			case 'dtos': $parentClass = '\devmo\dtos\Dto'; break;
		}
		if ($parentClass && !class_exists($parentClass))
			throw new CoreException('ClassNotFound',array('class'=>$parentClass,'file'=>$fileBox->getFile()));
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
			throw new CoreException('ClassNotController',array('class'=>$fileBox->getClass(),'file'=>$fileBox->getFile()));
		if (($obj instanceof Loader))
			$obj->setFileBox($fileBox);
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
		// log it
		Devmo::logError($e);
		// display it
		if ($e instanceof CoreException) {
			echo self::handleCoreException($e);
		} else {
			Config::isDebug()
				? Devmo::debug($e->__toString(),'log entry')
				: Devmo::debug($e->getMessage(),'See the error log for more details');
		}
	}

	public static function handleCoreException (CoreException $e) {
		if (Config::isCli()) {
			return Config::isDebug() ? (string)$e : $e->getMessage();
		} else if (Config::isDebug()) {
			$controller = self::getObject('devmo.controllers.Error','new');
			$controller->setException($e);
			return $controller->run($e->tokens);
		} else {
			return self::execute(Config::getRequestNotFoundController())->getRoot();
		}
	}

	public static function loadClass ($class) {
		if (strstr($class,'\\'))
			$class = str_replace(array('/','\\'),'.',$class);
		self::getObject($class,'load');
	}

	public static function formatPath ($path, $type, $context=null) {
		if (!preg_match('/^(?P<path>.*?\.)?(?P<type>[^\.]+\.)?(?P<class>[^\.]+)$/',$path,$parts))
			throw new InvalidException('path',$path);
		$parts += array('path'=>null,'type'=>null,'class'=>null);
		if ($parts['path']==null || $parts['path']=='.')
			$parts['path'] = $context;
		$type .= '.';
		if ($parts['type']!=$type) {
			$parts['path'] .= $parts['type'];
			$parts['type'] = $type;
		}
		return $parts['path'].$parts['type'].$parts['class'];
	}

	public static function formatRequestToPath ($request) {
		return preg_match('=(.*?)/?([^/]+)/?$=',$request,$matches)
				? str_replace('/','.',$matches[1].'.controllers.'.str_replace(' ','',ucwords(preg_replace('/[\-\+]+/',' ',$matches[2]))))
				: false;
	}
}


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
	private static $requestNotFoundController = 'devmo.controllers.FourOFour';
	private static $requestedController = null;
	private static $defaultController = null;
	private static $defaultNamespace = null;
	private static $errorLogFile = null;
	private static $request = null;
	private static $debug = false;
	private static $cli = false;
	public static function init () {
		self::$cli = (bool) Devmo::getValue('SHELL',$_SERVER,false);
	}

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
	public static function setRequestNotFoundController ($controller) {
		self::$requestNotFoundController = Core::formatPath($controller,'controllers');
	}
	public static function setRequest ($request=null) {
		if ($request && ($request = preg_replace('=/+=','/',$request)) && $request!='/') {
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
	public static function getRequestNotFoundController () {
		return self::$requestNotFoundController;
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
	public static function isDebug () {
		return self::$debug;
	}
	public static function isCli () {
		return self::$cli;
	}
	public static function getDefaultNamespace () {
		if (!self::$defaultNamespace)
			throw new CoreException('DefaultNamespaceNotDefined');
		return self::$defaultNamespace;
	}
}


class Box {
	public function __set ($name, $value) {
		return $this->{'set'.ucfirst($name)}($name,$value);
	}
	public function __get ($name) {
		return $this->{'get'.ucfirst($name)}();
	}
}


class FileBox extends Box {
	private $type;
	private $class;
	private $file;
	private $context;
	public function __construct(array $values) {
		$this->setType($values['type']);
		$this->setClass($values['class']);
		$this->setFile($values['file']);
		$this->setContext($values['context']);
	}
	public function setType ($type) {
		$this->type = $type;
	}
	public function getType () {
		return $this->type;
	}
	public function setClass ($class) {
		$this->class = $class;
	}
	public function getClass () {
		return $this->class;
	}
	public function setFile ($file) {
		$this->file = $file;
	}
	public function getFile () {
		return $this->file;
	}
	public function setContext ($context) {
		$this->context = $context;
	}
	public function getContext () {
		return $this->context;
	}
}


// check for magic quotes
if (get_magic_quotes_gpc())
	die("Magic Quotes Config is On... exiting.");
// set default exception handler
set_exception_handler(array('\devmo\libs\Core','handleException'));
spl_autoload_register(array('\devmo\libs\Core','loadClass'));
