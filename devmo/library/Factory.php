<?php
/**
 * Primary gateway for finding and creating objects
 * within the framework.
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
class Factory {
  private static $self;          // obj  single reference to self
  private static $files = array();




  /**
   * loads a controller class to be used for inheritence.
   *
   * @param controller class name (minuse the 'Controller')
   * @return Controller
   */
  public static function loadController ($controller) {
    //  data checks
    if (!$controller)
      throw new Error('Missing Controller File Name');
    $controller .= 'Controller';
    $class = ucfirst(basename($controller));
    //  check if we have already loaded it
    if (!class_exists($class))
      self::loadFile(Path::getFile(DEVMO_APP_DIR,'controllers','controllers',$controller,true));
    if (!class_exists($class))
      throw new CoreError('ClassNotFound',array('class'=>$class,'file'=>$controller));
    return $class;
  }




  /**
   * Returns an instance of the requested controller class
   *
   * @param controller class name (minuse the 'Controller'), load only (for controller parent classes), static if you don't want path traversal
   * @return Controller
   */
  public static function getController ($controller,$context=null) {
  	$path = Path::getPath($controller,$context);
    //  load object
    $ajax = false;
    if (substr($path,0,5)=='/ajax') {
    	$path = substr($path,5);
    	$ajax = true;
    }
    $class = self::loadController($path);
    $controller = self::loadObject($class,'new',$context);
    $controller->setContext(Path::getContext($path));
    $controller->setAjax($ajax);
    //  do some more checks
    if (!is_object($controller) || !($controller instanceof Controller))
      throw new CoreError('ClassNotController',array('class'=>$class,'file'=>$controller));
    return $controller;
  }




  /**
   * Finds and returns an instance of the view
   *
   * @param name of template file
   * @return View
   */
  public static function getView ($template) {
    //  data checks
    if (!$template || !($template = Path::getFile(DEVMO_VIEW_DIR,DEVMO_VIEW_SUBDIR,'views',$template)))
      throw new Error('Missing or Invalid Template');
    $view = self::loadObject('View','new');
    $view->setTemplate($template);
    return $view;
  }




  /**
   * Finds and returns requested dao name (+ 'Dao')
   *
   * @param dao name
   * @return dao
   */
  public static function loadDao ($dao) {
    //  data checks
    if (!$dao)
      throw new Error("Missing Class Name");
    //  data initializations
    if ($dao!='Dao' && $dao!='/Dao')
    	$dao .= 'Dao';
    $class = ucfirst(basename($dao));
    //  check if we have already loaded it
    if (!class_exists($class))
      self::loadFile(Path::getFile(DEVMO_APP_DIR,'daos','daos',$dao,true));
    if (!class_exists($class))
      throw new CoreError('ClassNotFound',array('class'=>$class,'file'=>$dao));
    return $class;
  }




  /**
   * Finds and returns requested dao name (+ 'Dao')
   *
   * @param Dao name
   * @return Dao
   */
  public static function getDao ($dao, $context=null) {
    $dao = self::loadObject(self::loadDao(Path::getPath($dao,$context)),'auto',$context);
    if (!is_object($dao) || !($dao instanceof Dao))
      throw new Error('Class Is Not A Dao: '.$class);
    return $dao;
  }




  /**
   * Finds and returns requested dao name (+ 'Dao')
   *
   * @param dao name
   * @return dao
   */
  public static function loadLibrary ($library) {
    //  data checks
    if (!$library)
      throw new Error("Missing Class Name");
    //  data initializations
    $class = ucfirst(basename($library));
    //  check if we have already loaded it
    if (!class_exists($class))
      self::loadFile(Path::getFile(DEVMO_APP_DIR,'library','library',$library,true));
    if (!class_exists($class))
      throw new CoreError('ClassNotFound',array('class'=>$class,'file'=>$library));
    return $class;
  }



  /**
   * Finds and returns the requested library object
   *
   * @param Library name, load option (auto, singleton, new, load)
   * @return Object
   */
  public static function getLibrary ($library, $context=null, $option='auto') {
    return self::loadObject(self::loadLibrary(Path::getPath($library,$context)),$option,$context);
  }




  /**
   * Finds and returns external extension class
   *
   * @param full file name, class name if you want an object returned
   * @return Object
   */
  public static function getExt ($file, $class=null) {
    //  data checks
    if (!$file)
      throw new Error("Missing Extension File Name");
		$file = DEVMO_EXT_DIR.'/'.$file;
    $flag = $file
      ? require_once($file)
      : false;
    return $flag && $class
      ? new $class
      : $flag;
  }




  /**
   * Private function used internally to load files
   *
   * @param file name and path
   * @return success
   */
  private static function loadFile ($file) {
    //  check for file
    if (!$file)
      throw new CoreError('FileNotFound',array('file'=>$file));
    if (!Util::getValue($file,self::$files)) {
      require_once($file);
      self::$files[$file]=1;
    }
  }




  /**
   * Private function used internally to load objects
   *
   * @param class name, load option
   * @return Object
   */
  private static function loadObject ($class, $option='auto', $context=null) {
    //  check for class
    if (!class_exists($class))
      throw new CoreError('ClassNotFound',array('class'=>$class,'file'=>'Unknown'));
    //  handle options
    $obj = null;
    switch ($option) {
      case 'auto':
        $obj = in_array('getInstance',get_class_methods($class))
          ? call_user_func(array($class,'getInstance'))
          : new $class;
        break;
      case 'singleton':
        // $obj = $class::getInstance(); // php5.3+ only
        $obj = call_user_func(array($class,'getInstance'));
        break;
      case 'new':
        $obj = new $class;
        break;
      case 'load':
        $obj = true;
        break;
    }
    if (($obj instanceof Loader) && !$obj->getContext())
    	$obj->setContext($context);
    return $obj;
  }


}  // EOC
?>