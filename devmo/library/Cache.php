<?php
/**
 * Common gateway for the memcache
 * caching mechanism
 * 
 * @category Utility
 * @author Dan Wager
 * @version 1.0
 */
class Cache {
  private static $self;
  private static $cache;
  private static $space;




  /**
   * Creates memcache object and 
   * connects to the host
   */
  private function __construct () {
    self::$cache = new Memcache();
    self::$cache->addServer('localhost');
    self::$space = array();
  }




  /**
   * Singleton Object Creator
   *
   * @return unknown
   */
  public static function getInstance () {
    if (!self::$self)
      self::$self = new Cache();
    return self::$self;
  }




  /**
   * Takes a plain text key, creates a namespace
   * if needed, and hashes it.
   *
   * @param $key    - requested plain key
   * @param $space  - name space
   * @return hashed key
   */
  private static function getKey ($key, $space = null) {
    // name space it
    if ($space) {
      if (!isset(self::$space[$space]))
        self::$space[$space] = 'space_' . $space . '_0_';
      $key = self::$space[$space] . $key;
    }
    
    //  hash it
    return md5($key);
  }



  /**
   * Adds a pair to the memcache hash
   *
   * @param $key   - hash key
   * @param $val   - hash value
   * @param $time  - cache retention in seconds
   */
  public static function set ($key, $val, $time=30, $space=null) {
    self::$cache->set(self::getKey($key,$space), $val, false, $time);
  }




  /**
   * Returns the value in memcache for the 
   * given key
   *
   * @param $key
   * @return value
   */
  public static function get ($key, $space = null) {
    return self::$cache->get(self::getKey($key,$space));
  }





  /**
   * Removes value associated with key from
   * memcache
   *
   * @param $key   - hash key
   * @return  value of hash for $key
   */
  public static function delKey ($key, $space = null, $time = 0) {
    return self::$cache->delete(self::getKey($key,$space), $time);
  }



  
  /**
   * Enter description here...
   *
   * @param unknown_type $space
   */
  public static function delSpace ($space) {
    self::$space[$space] = 
      preg_replace('/_([0-9]+)_$/e', "'_'.($1+1).'_'", self::$space[$space]);
  }




  /**
   * Expires all cached items on server
   *
   * @return  boolean success
   */
  public static function delCache () {
    return self::$cache->flush();
  }

}
?>