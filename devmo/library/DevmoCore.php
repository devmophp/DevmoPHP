<?php
class DevmoCore {
	public static $debug = false;
	public static $paths = array('controllers'=>array(),'views'=>array(),'libraries'=>array(),'daos'=>array());
	public static $folders = array('controllers'=>'controllers','views'=>'views','libraries'=>'libraries','daos'=>'daos');
	public static $mappings = array();
	public static $homeController = null;
	public static $requestedController = null;

  public static function execute ($name=false,$data=null) {
  	// find controller
  	if ($name) {
  		$controller = $name;
		} else if (self::$requestedController) {
			$controller = self::$requestedController;
		} else {
			$controller = self::$homeController;
		}
  	//	get controller view
    if (!$view = self::executeController($controller,$data))
	    throw new DevmoCoreException('ViewNotFound',array('controller'=>'?'));
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
		$controller = Devmo::getController($path);
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
		preg_match('/^[\/]*(.*?)([^\/]+)$/',$name,$matches);
		$name = preg_replace('/[ _-]+/','',ucwords($matches[2]));
		$context = "/{$matches[1]}";
		$subPath = $context.self::$folders[$type].'/'.$name.'.php';
		$file = null;
  	foreach (self::$paths[$type] as $path) {
  		$file = $path.$subPath;
			if (is_file($file)) {
				break;
			}
  	}
    //  check framwork core
    if (!is_file($file)) {
    	$devmoFile = DEVMO_DIR.'/'.$type.'/'.$name.'.php';
    	if (is_file($devmoFile))
		    $file = $devmoFile;
    }
    //  handle file not found
    if (!is_file($file))
      throw new DevmoCoreException('/FileNotFound',array('file'=>$file));
		// return file box
		$box = new DevmoBox;
		$box->class = $name;
		$box->file = $file;
		$box->context = $context;
		return $box;
	} 
	

	public static function getObject (DevmoBox $file, $parentClass=null, $option='auto') {
		require_once($file->file);
		if ($option=='load')
			return true;
    //  check for class
    $class = $file->class;
		// load file
    if (!class_exists($class))
      throw new DevmoCoreException('ClassNotFound',array('class'=>$class,'file'=>$file->getFile()));
    //  check for parent class
    if ($parentClass && !class_exists($parentClass))
      throw new DevmoCoreException('ClassNotFound',array('class'=>$parentClass,'file'=>$file->getFile()));
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
      throw new DevmoCoreException('ClassNotController',array('class'=>$file->getClass(),'file'=>$file->getFile()));
    if (($obj instanceof Loader) && !$obj->getContext())
    	$obj->setContext($file->context);
    return $obj;
	}


	public static function handleException (Exception $e) {
		self::$debug
			? debug($e->__toString(),'log entry')
			: debug($e->getMessage(),'See the error log for more details');
		if (!Logger::add($e->__toString()))
			debug(null,'Could not log error');
	}


  public static function sanitize (&$hash) {
    foreach ($hash as $k=>$v)
      is_array($v)
        ? self::sanitize($v)
        : $hash[$k] = htmlentities(trim($v),ENT_NOQUOTES);
  }

}


class DevmoBox {
	private $boxData;

	public function __construct () {
		$this->boxData = array();
	}

  public function __set ($name, $value) {
		if (empty($name)) 
			throw new InvalidDevmoException('Data Key',$name);
		return $this->boxData[$name] = $value;
  }

  public function __get ($name) {
  	return Devmo::getValue($name,$this->boxData);
  }

}


abstract class Dao {
}
