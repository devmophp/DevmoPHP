<?php
namespace devmo;

use \Devmo;
use \devmo\exceptions\CoreException;
use \devmo\exceptions\FileNotFoundCoreException;
use \devmo\exceptions\FileTypeNotFoundCoreException;
use \devmo\exceptions\InvalidException;

class Object {
	public function __toString () {
		return 'Object:\\'.get_class($this);
	}
	public static function debug ($mixed, $title=null, $option=null) {
		print Config::isCli() ? null : '<pre>'.PHP_EOL;
		print PHP_EOL.$title.PHP_EOL;
		switch ($option) {
			default:
				print_r($mixed);
				break;
			case 'fatal':
				print_r($mixed);
				exit;
				break;
			case 'trace':
				debug_print_backtrace();
				print_r($mixed);
				break;
			case 'obj':
				print_r($mixed);
				break;
			case 'xml':
				echo $mixed->asXML();
				break;
		}
		print Config::isCli() ? null : PHP_EOL.'</pre>';
	}
	public static function getValue ($needle, $haystack, $default=null) {
		if (is_array($haystack))
			return isset($haystack[$needle])
				? $haystack[$needle]
				: $default;
		if (is_object($haystack))
			return isset($haystack->{$needle})
				? $haystack->{$needle}
				: $default;
	}
	public static function classExists ($class) {
		return in_array(substr($class,1),get_declared_classes());
	}
}

class Core extends Object {
	private static $fileBoxes = array();
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

	private static function executeController ($path, array $args=null, $message=null) {
		//  get controller object
		$controller = self::load($path,'new');
		if ($message)
			$controller->setMessage($message);
		//	run controller
		$view = $controller->run($args);
		//  forward to next controller
		if ($controller->getForward())
			return self::executeController($controller->getForward(),$args,$message);
		// return only views
		return $view instanceof View ? $view : null;
	}

	public static function load ($path, $option='filebox', $args=null) {
		if (!$path)
			throw new InvalidException($path,'path');
		// allow paths with ns seperator
		if (strstr($path,'\\'))
			$path = str_replace('\\','.',(substr($path,0,1)=='\\'?substr($path,1):$path));
		if (!($fileBox = self::getValue($path,self::$fileBoxes))) {
			// find context and type
			preg_match('/^(.*?)([^\.]+)\.([^\.]+)$/',$path,$matches);
			$context = self::getValue(1,$matches);
			if (!isset($matches[2]) || !($folder = Config::getFolderForType($matches[2])))
				throw new FileTypeNotFoundCoreException(self::getValue(2,$matches),$path);
			$type = $matches[2];
			// put it together
			$fileBox = new FileBox();
			$fileBox->setPath($path);
			$fileBox->setType($type);
			$fileBox->setContext($context);
			if ($option!='filebox')
				$fileBox->setClass(str_replace('.','\\','.'.$matches[1].$matches[2].'.'.ucfirst($matches[3])));
			$xFile = null;
			foreach (Config::getNamespacePathForType($type) as $xNamespace=>$xPath) {
				if (preg_match("/^{$xNamespace}/",$context)>0) {
					$xName = preg_replace('/[ _-]+/','',ucwords($matches[3]));
					$xDir = preg_replace("/^{$xNamespace}/",$xPath,str_replace('.','/',$context)).($xNamespace=='devmo'?$type:$folder);
					$xFile = $xDir.'/'.$xName.'.php';
					if (is_file($xFile)) {
						$fileBox->setFile($xFile);
						break;
					}
				}
			}
			if ($xFile==null)
				throw new CoreException('NamespaceNotDefined',array('path'=>$path,'namespace'=>$context,'namespaces'=>implode(',',Config::getNamespacesForType($type))));
			self::$fileBoxes[$path] = $fileBox;
		}
		// check for file
		if ($option=='filebox') {
			if (!$fileBox->getFile())
				throw new FileNotFoundCoreException($path,self::getRequest());
			return $fileBox;
		}
		// check for class
		if (!self::classExists($fileBox->getClass())) {
			if (!$fileBox->getFile())
				throw new FileNotFoundCoreException($fileBox->getPath(),Config::getRequest());
			require_once($fileBox->getFile());
			if (!self::classExists($fileBox->getClass()))
				throw new CoreException('ClassNotFound',array('class'=>$fileBox->getClass(),'file'=>$fileBox->getFile()));
		}
		// return if we aren't doing anything else
		if ($option=='static')
			return true;
		// check for parent class
		$parentClass = null;
		switch ($fileBox->getType()) {
			case 'controllers': $parentClass = '\devmo\Controller'; break;
			case 'views': $parentClass = '\devmo\View'; break;
			case 'daos': $parentClass = '\devmo\Dao'; break;
			case 'dtos': $parentClass = '\devmo\Dto'; break;
		}
		if ($parentClass && !self::classExists($parentClass))
			throw new CoreException('ClassNotFound',array('class'=>$parentClass,'file'=>$fileBox->getFile()));
		//  handle options
		$obj = null;
		$class = $fileBox->getClass();
		switch ($option) {
			default:
			case 'auto':
				$obj = in_array('getInstance',get_class_methods($class))
					? call_user_func(array($class,'getInstance'))
					: new $class;
				break;
			case 'singleton':
				$obj = $class::getInstance($args);
				break;
			case 'new':
				$obj = new $class($args);
				break;
		}
		if ($parentClass && !($obj instanceof $parentClass))
			throw new CoreException('ClassNotController',array('class'=>$fileBox->getClass(),'file'=>$fileBox->getFile()));
		if ($obj instanceof Loader)
			$obj->setFileBox($fileBox);
		return $obj;
	}

	public static function makeSafe ($value) {
		if (is_array($value)) {
			foreach ($value as $k=>$v) {
				$value[$k] = self::makeSafe($v);
			}
			return $value;
		} else {
			return htmlentities(trim($value),ENT_NOQUOTES);
		}
	}

	public static function handleError ($number, $message, $file=null, $line=null, array $context=null) {
		//256	E_USER_ERROR	512	E_USER_WARNING	1024	E_USER_NOTICE
		// log it
		Devmo::logError("Error [{$number}] {$message} file:{$file}:{$line}\n");
		// use default error handler for everything else
		return false;
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
			$controller = self::load('devmo.controllers.Error','new');
			$controller->setException($e);
			return $controller->run($e->tokens);
		} else {
			return self::execute(Config::getRequestNotFoundController())->getRoot();
		}
	}

  public static function loadClass ($class) {
    if (strstr($class,'\\'))
      $class = str_replace(array('/','\\'),'.',$class);
    if (substr($class,0,1)=='.')
      $class = substr($class,1);
    try {
      return self::load($class,'static');
    } catch (\Exception $e) {
      return !(($e instanceof FileNotFoundCoreException || $e instanceof FileTypeNotFoundCoreException) && count(spl_autoload_functions())>1)
				? self::handleException($e)
				: false;
    }
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

	public static function formatControllerToRequest ($controller) {
		return preg_replace('='.Config::getRequest().'$=','',self::getValue('PHP_SELF',$_SERVER))
				.preg_replace(
						array('/^'.Config::getDefaultNamespace().'/','/controllers?\./','/\.+/'),
						array('','','/'),
						$controller);
	}

}

class Config extends Object{
	private static $typeFolderMap = array(
			'exceptions'=>'_exceptions',
			'controllers'=>'_controllers',
			'logic'=>'_logic',
			'daos'=>'_daos',
			'dtos'=>'_dtos',
			'views'=>'_views',
			'libs'=>'_libs',
			'includes'=>'_incs');
	private static $typeNamespacePathMap = array(
			'exceptions'=>array(),
			'controllers'=>array(),
			'logic'=>array(),
			'daos'=>array(),
			'dtos'=>array(),
			'views'=>array(),
			'libs'=>array(),
			'includes'=>array());
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
		self::$cli = (bool) self::getValue('SHELL',$_SERVER,false);
	}

	# for application use
	public static function addNamespacePathMapping ($namespace, $path, $default=false) {
		foreach (self::$typeNamespacePathMap as $k=>$v) {
			self::$typeNamespacePathMap[$k][$namespace] = $path;
			uasort(self::$typeNamespacePathMap[$k],function ($a,$b) { return strlen($b)-strlen($a); });
		}
		if ($default || (self::$defaultNamespace==null && $namespace!='devmo'))
			self::$defaultNamespace = $namespace;
	}
	public static function getPathForNamespace ($namespace, $type='controllers') {
		if (!isset(self::$typeNamespacePathMap[$type]))
			throw new InvalidException('Path Type',$type);
		return self::$typeNamespacePathMap[$type][$namespace];
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
	public static function getTypes () {
		return array_keys(self::$typeFolderMap);
	}
	public static function getFolderForType ($type) {
		return self::getValue($type,self::$typeFolderMap);
	}
	public static function getNamespacesForType ($type) {
		return array_keys(self::getNamespacePathForType($type));
	}
	public static function getNamespacePathForType ($type) {
		return self::getValue($type,self::$typeNamespacePathMap);
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
class Box extends Object {
	public function __set ($name, $value) {
		return $this->{'set'.ucfirst($name)}($value);
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
	private $path;
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
	public function setPath ($path) {
		$this->path = $path;
	}
	public function getPath () {
		return $this->path;
	}
	public function copy (FileBox $fb) {
		$this->class = $fb->getClass();
		$this->context = $fb->getContext();
		$this->file = $fb->getFile();
		$this->path = $fb->getPath();
		$this->type = $fb->getType();
	}
}

class Loader extends Object {
	private $fb = null;
	protected function get ($path, $args=null, $option='auto') {
		return Core::load(($path && substr($path,0,1)=='.' ? $this->getContext().substr($path,1) : $path),$option,$args);
	}
	protected function exists ($path) {
		return Core::load(($path && substr($path,0,1)=='.' ? $this->getContext().substr($path,1) : $path),'check');
	}
	public function setFileBox (FileBox $fileBox) {
		$this->fb = $fileBox;
	}
	public function getClass () {
		return $this->fb->getClass();
	}
	public function getContext () {
		return $this->fb->getContext();
	}
}

abstract class Controller extends Loader {
  protected $forward = null;
  protected $do = null;
	protected $message = null;
  protected $ajax = false;

	public function setAjax ($ajax) {
		$this->ajax = $ajax;
	}

	public function isAjax () {
		return $this->ajax;
	}

  public function setForward ($controller) {
  	$this->forward = Core::formatPath($controller,'controllers',$this->getContext());
  }

  public function getForward () {
    return $this->forward;
  }

  public function getDo () {
    return $this->do;
  }

  public function setMessage ($message) {
  	$this->message = $message;
  }

  public function getMessage () {
  	return $this->message;
  }

  protected function getView ($path=null, $tokens=null) {
  	if (!$path)
  		$path = basename(str_replace('\\','/',$this->getClass()));
		$path = Core::formatPath($path,'views',$this->getContext());
		return new \devmo\View($path,$tokens);
  }

	protected function getGet ($name, $default=false, $makeSafe=true) {
		return (($value = self::getValue($name,$_GET,$default)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getPost ($name, $default=false, $makeSafe=true) {
		return (($value = self::getValue($name,$_POST,$default)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getSession ($name, $default=false) {
		if (!isset($_SESSION))
			throw new \devmo\exceptions\Exception('session does not exist');
		return self::getValue($name,$_SESSION,$default);
	}

	protected function getRequest ($name, $default=false, $makeSafe=true) {
		return (($value = self::getValue($name,$_REQUEST,$default)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getRequestController () {
		return Config::getRequestedController()
			? Config::getRequestedController()
			: Config::getDefaultController();
	}

	protected function getServer ($name, $default=false) {
		return $this->getValue($name,$_SERVER,$default);
	}

  protected function runController ($controller, $args=null) {
  	return Core::execute(Core::formatPath($controller,'controllers',$this->getContext()),$args);
  }

	protected function runRequest ($request, $args=null) {
		return Core::execute(Core::formatRequestToPath($request),$args);
	}

	protected function formatRequest ($controller=null, array $get=null) {
		$request = $controller===null
				? Core::formatControllerToRequest($this->getPath())
				: Core::formatControllerToRequest(Core::formatPath($controller,'controller',$this->getContext()));
		if (count($get)>0) {
			$request .= '?';
			foreach ($get as $k=>$v) {
				$request .= '&'.urlencode($k).'='.urlencode($v);
			}
		}
		return $request;
	}

	protected function redirect ($controller, array $get=null) {
		$request = $this->formatRequest($controller,$get);
		$view = $this->getView('devmo.HttpRaw');
		$view->code = 200;
		$view->headers = array("Location: {$request}");
		print $view;
		exit;
	}

  abstract public function run (array $args=null);
}


class View extends Object {
	private $myPath = null;
	private $myParent = null;
	private $myTokens = null;

	public function __construct ($path, $tokens=null) {
		$this->myPath = $path;
		if (!$tokens) {
			$this->myTokens = new \stdClass;
		} else if (is_array($tokens)) {
			$this->myTokens = (object) $tokens;
		} else if (is_string($tokens)) {
			$this->myTokens = (object) array('echo'=>$tokens);
		} else if (is_object($tokens)) {
			$this->myTokens = $tokens;
		}
	}

  public function __set ($name, $value) {
		if ($value===$this)
			throw new DevmoException('Token Value Is Circular Reference');
		if (is_object($value) && $value instanceof View)
			$value->parent = $this;
		$this->myTokens->{$name} = $value;
  }

	public function __get ($name) {
		return $this->getValue($name,$this->myTokens);
	}

	public function __toString () {
		ob_start();
		try {
			require(Core::load($this->myPath,'filebox')->getFile());
		} catch (\Exception $e) {
			Core::handleException($e);
		}
		$x = ob_get_contents();
		ob_end_clean();
		return $x;
	}

	public function getRoot () {
		return $this->myParent
			? $this->myParent->getRoot()
			: $this;
	}

	public function setTokens ($tokens) {
		if (is_array($tokens) || is_object($tokens)) {
			foreach ($tokens as $k=>$v) {
				$this->set($k,$v);
			}
		}
	}

	public function getTokens () {
		return $this->myTokens;
	}
}


abstract class Logic extends Loader {}


abstract class Dao extends Object {}


abstract class Dto extends \devmo\Box {
	protected $id;
	public function __construct ($record=null, $validate=false) {
		if ($record!==null) {
			if ($record!=null && !(is_object($record) || is_array($record)))
				throw new \devmo\exceptions\Exception('record is not iterable');
			$fields = $this;
			if ($validate) {
				$fields = array_intersect_key(
					is_object($record) ? get_object_vars($record) : $record,
					get_object_vars($this)
				);
			}
			foreach ($fields as $k=>$v)
				if ($validate)
					$this->__set($k, self::getValue($k,$record));
				else
					$this->{$k} = self::getValue($k,$record);
		}
	}
	public function setId ($id) {
		if (!preg_match('/^\d+$/',$id))
			throw new InvalidException('id',$id);
		$this->id = $id;
	}
	public function getId () {
		return $this->id;
	}
}


class Exception extends \LogicException {
  private $path;
  private $info;

  public function __construct ($text, $path=null) {
    parent::__construct($text);
    $this->path = $path;
    $this->extra = null;
  }

  public function getPath () {
    return $this->path;
  }

  public function setInfo ($info) {
    $this->info = $info;
  }

  public function __toString () {
		$err = "What: ".$this->getMessage()
				.PHP_EOL."When: ".date('Y-m-d H:m:s')
				.($this->path ? PHP_EOL."Path: {$this->path}" : null)
				.($this->info ? PHP_EOL."Info: {$this->info}" : null)
				.PHP_EOL."Where: {$this->file}:{$this->line}";
    foreach ($this->getTrace() as $i=>$x) {
			$args = "";
			foreach ($x['args'] as $xa) {
				if (is_array($xa)) {
					$args .= ($args?',':null).'array';
				} else if (is_object($xa)) {
					$args .= ($args?',':null).get_class($xa);
				} else {
					$args .= ($args?',':null).var_export($xa, true);
				}
			}
      $err .= PHP_EOL
					.(isset($x['file'])?"{$x['file']}:{$x['line']}":null)
					.(isset($x['class'])?" {$x['class']}{$x['type']}":null)
					.(isset($x['function'])?$x['function'].'('.$args.') ':null);
    }
		$err .= PHP_EOL;
    return $err;
  }

  public function __toViewString () {
		$err = "What: ".$this->getMessage()
				.PHP_EOL."When: ".date('Y-m-d H:m:s')
				.($this->path ? PHP_EOL."Path: {$this->path}" : null)
				.($this->info ? PHP_EOL."Info: {$this->info}" : null)
				.PHP_EOL."Where: ";
		$devmoPath = Config::getPathForNamespace('devmo');
		$trace = $this->getTrace();
		foreach ($trace as $i=>$x) {
			$args = array();
			foreach ($x['args'] as $xa) {
				if (is_array($xa)) {
					$args[] = 'Array';
				} else if (is_object($xa)) {
					$args[] = 'Object';
				} else {
					$args[] = $xa;
				}
				$err .= (isset($x['file'])?"{$x['file']}:{$x['line']}":null)
						 .(isset($x['class'])?" {$x['class']}{$x['type']}":null)
						 .(isset($x['function'])?$x['function'].'('.implode(', ',$args).') ':null)
						 .PHP_EOL;
			}
		}
    return $err;
  }

}


// check for magic quotes
if (get_magic_quotes_gpc())
	die("Magic Quotes Config is On... exiting.");
// set default exception handler
set_error_handler(array('\devmo\Core','handleError'));
set_exception_handler(array('\devmo\Core','handleException'));
spl_autoload_register(array('\devmo\Core','loadClass',true));
