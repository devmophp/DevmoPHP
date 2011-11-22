<?php
/**
 * Manages the file creation, file size,
 * and file rolling of logs for the site.
 *
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @category Utility
 * @version 1.0
 */
namespace Devmo\libs;

class Logger {
  private static $_self;
  private static $dir;
  private static $mbs = 2044;
  private static $num = 2;
	private static $roll = false;
	private static $file = null;
 
 
  public static function setDefaultFile ($file) {
    self::$file = $file;
  }


  /**
   * Add line to log
   *
   * @param log file name, log message
   * @return void
   */
  public static function add ($text, $file=null) {
    // error checking
    if (!$file)
			$file = self::$file;
    // roll file
    $success = self::$roll ? self::roll($file) : true;
    // write message to log
    if ($success) 
      $success = file_put_contents($file, $text."\n", FILE_APPEND);
    return $success;
  }

 



  /**
   * Returns the size in megabytes of the current log file
   *
   * @param none
   * @return number of megabytes
   */
  public static function getSize () {
    return self::$mbs;
  } 
 



  /**
   * Returns the max number of files to roll
   *
   * @param none
   * @return Number of roll files
   */
  public static function getNum () {
    return self::$num;
  } 



  /**
   * Sets the size in megabytes of a file before rolling
   *
   * @param Number of megabytes
   * @return void
   */
  public static function setSize ($mbs) {
    self::$mbs = $mbs;
  }
 



  /**
   * Set the max number of file rolls
   *
   * @param Number of max rolls
   * @return void
   */
  public static function setNum ($num) {
    self::$num = $num;
  }
 



  /**
   * Private method to roll log file on max size
   *
   * @param log file name
   * @return success
   */
  private static function roll ($file) {
    // error checking
    if (!$file) return FALSE;
    //  data instantiations
    $success = true;
    //  check file size
    $mbs = file_exists($file)
      ? round((filesize($file)/1048576),3)
      : 0;
    if ($mbs >= self::getSize()) {
      //  roll all log files back
      for ($x=self::getNum(); $x>=1; $x--) {
        if (file_exists($file.'.'.($x-1))) {
          if (!rename($file.'.'.($x-1),$file.'.'.$x)) {
            $success = false;
          }
        }
      }
      //  roll primary log
      if (!rename($file,$file.'.1')) {
        $success = false;
      }
    }
    return $success;
  }

} // EOC
