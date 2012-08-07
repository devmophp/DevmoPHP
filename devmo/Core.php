<?php
namespace devmo;

use \Devmo;
use \devmo\exceptions\CoreException;
use \devmo\exceptions\InvalidException;

class Object {
	public function __toString () {
		return 'Object:\\'.get_class($this);
	}
	public static function debug ($mixed, $title=null, $option=null) {
		print Config::isCli() ? null : '<pre>'.PHP_EOL;
		print PHP_EOL.$title.PHP_EOL;
		switch ($mixed) {
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

	public static function getObject ($path, $option='auto', $args=null) {
		// get file box
		if (!($fileBox = self::getValue($path,self::$fileBoxes)))
			$fileBox = self::$fileBoxes[$path] = new FileBox($path);
		// check for class again
		$class = $fileBox->getClass();
		if (!class_exists($class))
			throw new CoreException('ClassNotFound',array('class'=>$class,'file'=>$fileBox->getFile()));
		// return if we aren't doing anything else
		if ($option=='load')
			return true;
		// check for parent class
		$parentClass = null;
		switch ($fileBox->getType()) {
			case 'controllers': $parentClass = '\devmo\Controller'; break;
			case 'views': $parentClass = '\devmo\View'; break;
			case 'daos': $parentClass = '\devmo\Dao'; break;
			case 'dtos': $parentClass = '\devmo\Dto'; break;
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
				$obj = $class::getInstance($args);
				break;
			case 'new':
				$obj = new $class($args);
				break;
		}
		if ($parentClass && !($obj instanceof $parentClass))
			throw new CoreException('ClassNotController',array('class'=>$class,'file'=>$fileBox->getFile()));
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
		if (substr($class,0,1)=='.')
			$class = substr($class,1);
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

	public static function formatControllerToRequest ($controller) {
		return preg_replace('='.Config::getRequest().'$=','',Devmo::getValue('PHP_SELF',$_SERVER))
				.preg_replace(
						array('/^'.Config::getDefaultNamespace().'/','/controllers?\./','/\.+/'),
						array('','','/'),
						$controller);
	}

}

class Config {
	private static $typeDirectoryMap = array(
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
		self::$cli = (bool) Devmo::getValue('SHELL',$_SERVER,false);
	}

	# for application use
	public static function addNamespacePathMapping ($namespace, $path, $default=false) {
		foreach (self::$typeNamespacePathMap as $k=>$v) {
			self::$typeNamespacePathMap[$k][$namespace] = $path;
			uasort(self::$typeNamespacePathMap[$k],function ($a,$b) { return strlen($a)-strlen($b); });
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

class Box extends Object {
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
	private $path;
	public function __construct($path) {
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
		$this->setPath($path);
		$this->setClass(str_replace('.','\\','.'.$path));
		$this->setType($type);
		$this->setContext($context);
		if (!class_exists($this->getClass())) {
			$xFile = null;
			foreach ($typeNamespacePathMap[$type] as $xNamespace=>$xPath) {
				if (preg_match("/^{$xNamespace}/",$context)>0) {
					$xName = preg_replace('/[ _-]+/','',ucwords($matches[3]));
					$xDir = preg_replace("/^{$xNamespace}/",$xPath,str_replace('.','/',$context)).($xNamespace=='devmo'?$type:$typeDirectoryMap[$type]);
					$xFile = $xDir.'/'.$xName.'.php';
					if (is_file($xFile)) {
						require_once($xFile);
						$this->setFile($xFile);
					}
				}
			}
			if ($xFile==null)
				new CoreException('NamespaceNotDefined',array('path'=>$path,'context'=>$context,'namespaces'=>implode(',',array_keys($typeNamespacePathMap[$type]))));
			if (!$this->getFile())
				new CoreException('FileNotFound',array('request'=>$xFile));
		}
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
	public function setPath ($path) {
		$this->path = $path;
	}
	public function getPath () {
		return $this->path;
	}
}


class Loader extends Object {
	private $fileBox = null;

  public function setFileBox (\devmo\FileBox $fileBox) {
  	$this->fileBox = $fileBox;
  }

	protected function getFileBox () {
		return $this->fileBox;
	}

  public function getContext () {
  	return $this->fileBox->getContext();
  }

	protected function getPath () {
		return $this->fileBox->getPath();
	}

  protected function get ($path, $args=null, $option='auto') {
		if ($path[0]=='.')
			$path = $this->fileBox->getContext().substr($path,1);
  	return Core::getObject($path,$option,$args);
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
  	if (!($this instanceof \devmo\Controller))
			throw new CoreException('ClassNotController',array('class'=>$this->getFileBox()->getClass(),'file'=>$this->getFileBox()->getFile()));
  	if (!$path)
  		$path = basename(str_replace('\\','/',$this->getFileBox()->getClass()));
		$fileBox = Core::getFileBox(Core::formatPath($path,'views',$this->getFileBox()->getContext()));
		$view = new \devmo\View();
		$view->setTemplate($fileBox->file);
		if ($tokens)
			$view->setTokens($tokens);
  	return $view;
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
	private $parent;	//	ref to parent view object
	private $template;	//	str	template file
	private $tokens;

	public function __construct () {
		$this->parent = null;
		$this->template = null;
		$this->tokens = new \stdClass;
	}

  public function __set ($name, $value) {
		if ($value===$this)
			throw new DevmoException('Token Value Is Circular Reference');
		if (is_object($value) && $value instanceof View)
			$value->parent = $this;
		$this->set($name,$value);
  }

	public function __get ($name) {
		return $this->get($name);
	}

	public function __toString () {
		if (!$this->getTemplate())
			throw new Error("Missing or Invalid Output File:".$this->getTemplate());
		//	execute view code and capture output
		ob_start();
		try {
			include($this->getTemplate());
		} catch (\Exception $e) {
			Core::handleException($e);
		}
		$output = ob_get_contents();
		ob_end_clean();
		//	return executed code as string
		return $output;
	}

	public function getRoot () {
		return $this->parent
			? $this->parent->getRoot()
			: $this;
	}

	public function setTemplate ($template) {
		//	error checks
		if (!$template)
			throw new Error('Missing Template');
		//	set template
		$this->template = $template;
	}

	public function getTemplate () {
		return $this->template;
	}

	public function set ($name, $value) {
		$this->tokens->{$name} = $value;
	}

	public function get ($name) {
		return $this->getValue($name,$this->tokens);
	}

	public function setTokens ($tokens) {
		if (is_array($tokens) || is_object($tokens)) {
			foreach ($tokens as $k=>$v) {
				$this->set($k,$v);
			}
		}
	}

	public function getTokens () {
		return $this->tokens;
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
			foreach ($this as $k=>$v)
				if ($validate)
					$this->__set($k, $v);
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
					$args .= ($args?',':null).$xa;
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
			if (!preg_match("=^{$devmoPath}=",Devmo::getValue('file',$x))) {
				$args = array();
				foreach ($x['args'] as $xa) {
					if (is_array($xa)) {
						$args[] = 'Array';
					} else if (is_object($xa)) {
						$args[] = 'Object';
					} else {
						$args[] = $xa;
					}
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
spl_autoload_register(array('\devmo\Core','loadClass'));
