<?php
class Factory {
  public static function loadController ($controller) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getController()",E_USER_DEPRECATED);
		return Devmo::getController($controller,true);
	}
  	
	public static function getController ($controller) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getController()",E_USER_DEPRECATED);
		return Devmo::getController($controller);
	}

	public static function getView ($template) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getView()",E_USER_DEPRECATED);
		return Devmo::getView($template);
	}
	
  public static function loadDao ($dao) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getDao()",E_USER_DEPRECATED);
		return Devmo::getDao($dao,true);
  }

  public static function getDao ($dao) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getDao()",E_USER_DEPRECATED);
		return Devmo::getDao($dao);
  }

  public static function loadLibrary ($library) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getLibrary()",E_USER_DEPRECATED);
		return Devmo::getLibrary($library,'load');
	}

  public static function getLibrary ($library, $context=null, $option='auto') {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getLibrary()",E_USER_DEPRECATED);
		return Devmo::getLibrary($library,$option);
  }
}

class Util {

  public static function getGet ($key) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getGet()",E_USER_DEPRECATED);
		Devmo::getGet($key);
	}

  public static function getPost ($key) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getPost()",E_USER_DEPRECATED);
		Devmo::getPost($key);
	}

  public static function getRequest ($key) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getRequest()",E_USER_DEPRECATED);
		Devmo::getRequest($key);
	}
	
  public static function getSession ($key) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getSession()",E_USER_DEPRECATED);
		Devmo::getSession($key);
	}

  public static function getValue ($key,$mixed) {
		trigger_error("Deprecated method ".__FUNCTION__." use Devmo::getValue()",E_USER_DEPRECATED);
		Devmo::getValue($key,$mixed);
	}

}