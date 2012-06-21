<?php
namespace devmo;

use \Devmo;
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

	public static function getObject ($path, $option='auto', $args=null) {
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
			throw new CoreException('ClassNotController',array('class'=>$fileBox->getClass(),'file'=>$fileBox->getFile()));
		if (($obj instanceof Loader))
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
						array('/^'.Config::getDefaultNamespace().'/','/controller\./','/\.+/'),
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
		foreach (self::$typeNamespacePathMap as $k=>$v)
			self::$typeNamespacePathMap[$k][$namespace] = $path;
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

class Object {
	public function __toString () {
		return 'Object:\\'.get_class($this);
	}
	protected static function debug ($mixed, $title=null, $option=null) {
		Devmo::debug($mixed,$title,$option);
	}
	protected static function getValue ($key, $mixed, $default=false) {
		return Devmo::getValue($key,$mixed,$default);
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


class Loader extends Object {
	private $fileBox = null;

  public function setFileBox (\devmo\Box $fileBox) {
  	$this->fileBox = $fileBox;
  }

  public function getContext () {
  	return $this->fileBox->getContext();
  }

  protected function get ($path, $args=null, $option='auto') {
		if ($path[0]=='.')
			$path = $this->fileBox->getContext().substr($path,1);
  	return Core::getObject($path,$option,$args);
  }

  protected function getView ($path=null, $tokens=null) {
  	if (!($this instanceof \devmo\Controller))
			throw new CoreException('ClassNotController',array('class'=>$this->fileBox->getClass(),'file'=>$this->fileBox->getFile()));
  	if (!$path)
  		$path = basename(str_replace('\\','/',$this->fileBox->getClass()));
		$fileBox = Core::getFileBox(Core::formatPath($path,'views',$this->fileBox->getContext()));
		$view = new \devmo\View();
		$view->setTemplate($fileBox->file);
		if ($tokens)
			$view->setTokens($tokens);
  	return $view;
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

	protected function getGet ($name, $default=false, $makeSafe=true) {
		return (($value = $this->getValue($name,$_GET,$default)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getPost ($name, $default=false, $makeSafe=true) {
		return (($value = $this->getValue($name,$_POST,$default)) && $makeSafe)
			? Core::makeSafe($value)
			: $value;
	}

	protected function getSession ($name, $default=false) {
		if (!isset($_SESSION))
			throw new \devmo\exceptions\Exception('session does not exist');
		return $this->getValue($name,$_SESSION,$default);
	}

	protected function getRequest ($name, $default=false, $makeSafe=true) {
		return (($value = $this->getValue($name,$_REQUEST,$default)) && $makeSafe)
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

	protected function formatRequest ($controller, array $get=null) {
		$request = Core::formatControllerToRequest(Core::formatPath($controller,'controller',$this->getContext()));
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
		include($this->getTemplate());
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
	public function __construct ($record=null) {
		if ($record!==null) {
			if ($record!=null && !(is_object($record) || is_array($record)))
				throw new \devmo\exceptions\Exception('record is not iterable');
			foreach ($this as $k=>$v)
				$this->{$k} = $this->getValue($k,$record);
		}
		$this->init();
	}
	protected function init () {}
	public function setId ($id) {
		return ($this->id = $id);
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
      $err .= PHP_EOL
					.(isset($x['file'])?"{$x['file']}:{$x['line']}":null)
					.(isset($x['class'])?" {$x['class']}{$x['type']}":null)
					.(isset($x['function'])?$x['function'].'('.implode(', ',$x['args']).') ':null);
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
		foreach ($this->getTrace() as $x) {
			if (!preg_match("=^{$devmoPath}=",$x['file'])) {
				$err .= (isset($x['file'])?"{$x['file']}:{$x['line']}":null)
						 .(isset($x['class'])?" {$x['class']}{$x['type']}":null)
						 .(isset($x['function'])?$x['function'].'('.implode(', ',$x['args']).') ':null);
				break;
			}
		}
    return $err;
  }

}


// check for magic quotes
if (get_magic_quotes_gpc())
	die("Magic Quotes Config is On... exiting.");
// set default exception handler
set_exception_handler(array('\devmo\Core','handleException'));
spl_autoload_register(array('\devmo\Core','loadClass'));
