<?php
namespace devmo\daos;

use \devmo\exceptions\CoreException;
use \InvalidArgumentException;
/**
 * Common gateway for database queries and tools.
 *
 * @category Utility
 * @author Dan Wager
 * @version 1.0
 */
class Database extends \devmo\Dao {
	private $host;
	private $name;
	private $user;
	private $pass;
	private $record;
	private $records;
	private $iterator;


	/**
	 * __construct
	 *
	 * initializes class properties
	 *
	 * @access public
	 * @return void
	 */
	public function __construct ($host, $user, $pass, $name=null) {
		if (!defined('DATABASE_DATE_FORMAT'))
			define('DATABASE_DATE_FORMAT','Y-m-d');
		if (!defined('DATABASE_DATE_FIRST_FORMAT'))
			define('DATABASE_DATE_FIRST_FORMAT','Y-m-01');
		if (!defined('DATABASE_DATETIME_FORMAT'))
			define('DATABASE_DATETIME_FORMAT','Y-m-d H:i:s');
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->name = $name;
		$this->dbk = $this->host.$this->user.$this->name;
		if (!DatabaseBox::getDbh($this->dbk))
			$this->connect();
	}


	/**
	 * connect
	 *
	 * handles database connection
	 *
	 * @access private
	 * @return void
	 */
	protected function connect () {
		if (!DatabaseBox::getDbh($this->dbk)) {
			$mysqli = new \mysqli($this->host,$this->user,$this->pass,$this->name);
			if (!($mysqli instanceof \mysqli))
				throw new CoreException('Database',array('error'=>'Could not connect to the database'));
			DatabaseBox::setDbh($this->dbk,$mysqli);
			if ($this->name!=null)
				$this->useSchema($this->name);
		}
		return true;
	}

	protected function useSchema ($name) {
		DatabaseBox::getDbh($this->dbk)->select_db($name);
	}


	/**
	 * disconnect
	 *
	 * discards current db connection
	 *
	 * @access private
	 * @return void
	 */
	protected function disconnect () {
		if (DatabaseBox::getDbh($this->dbk))
			DatabaseBox::getDbh($this->dbk)->close();
		return true;
	}


	/**
	 * query
	 *
	 * @access protected
	 * @param mixed $sql
	 * @return void
	 */
	protected function query ($sql, $add=null, $debug=false) {
		if ($debug)
			self::debug($sql,'Database::query::sql');
		$dbh = DatabaseBox::getDbh($this->dbk);
		if (!$result = $dbh->query($sql))
			throw new CoreException('Database',array('errorno'=>$dbh->errno,'error'=>$dbh->error.PHP_EOL.preg_replace('=\s+=',' ',$sql)));
		if ($result instanceof \mysqli_result)
			return new ResultSetDatabaseDao($result);
		if ($add) {
			$id = DatabaseBox::getDbh($this->dbk)->insert_id;
			if ($add instanceof \devmo\Dto)
				$add->setId($id);
			return $id;
		}
		return true;
	}



	/**
	 * formatDate
	 *
	 * @access protected
	 * @param mixed $date
	 * @return void
	 */
	protected function formatDate ($date=null, $nullable=true, $first=false) {
		if (($date===null && !$nullable) || ($date!==null && !($date = strtotime($date))))
			throw new InvalidArgumentException('Invalid Date');
		return $date===null ? 'NULL' : "'".date(($first?DATABASE_DATE_FIRST_FORMAT:DATABASE_DATE_FORMAT),$date)."'";
  }


	/**
	 * formatDateTime
	 *
	 * @access protected
	 * @param mixed $dateTime
	 * @return void
	 */
	protected function formatDateTime ($dateTime=null, $nullable=true) {
		if (($dateTime===null && !$nullable) || ($dateTime!==null && !($dateTime = strtotime($dateTime))))
			throw new InvalidArgumentException('Invalid DateTime');
		return $dateTime===null ? 'NULL' : "'".date(DATABASE_DATETIME_FORMAT,$dateTime)."'";
  }


	/**
	 * formatNumber
	 *
	 * @access protected
	 * @param mixed $number
	 * @return void
	 */
	protected function formatNumber ($number=null, $nullable=true) {
		if (($number===null && !$nullable) || ($number!==null && !is_numeric($number)))
			throw new InvalidArgumentException('Invalid Number');
		return $number===null ? 'NULL' : $number;
  }


	/**
	 * formatText
	 *
	 * @access protected
	 * @param mixed $text
	 * @return void
	 */
	protected function formatText ($text=null, $nullable=true) {
		if (!$nullable && $text===null)
			throw new InvalidArgumentException('Invalid Text');
		return $text===null ? 'NULL' : "'".DatabaseBox::getDbh($this->dbk)->real_escape_string($text)."'";
  }

}



class DatabaseBox {
	public static $dbhs = array();

	public static function getDbh ($dbhKey) {
		return empty(self::$dbhs[$dbhKey])
			? false
			: self::$dbhs[$dbhKey];
	}

	public static function setDbh ($dbhKey,$dbh) {
		self::$dbhs[$dbhKey] = $dbh;
	}
}



class ResultSetDatabaseDao implements \Iterator, \Countable {
	private $dto = '\stdClass';
	private $result = null;
	private $position = 0;

	public function __construct ($result) {
		if (!($result instanceof \mysqli_result))
			throw new InvalidArgumentException('DB Query Resource');
		$this->result = $result;
		$this->position = 0;
	}

	function __destruct () {
		$this->result->free_result();
	}

	public function setDto ($dto) {
		$this->dto = $dto;
		return $this;
	}

	function rewind () {
		$this->position = 0;
		if ($this->getNumRecords()>0)
			$this->result->data_seek($this->position);
	}

	function current () {
		return $this->result->fetch_object($this->dto);
	}

	function key () {
		return $this->position;
	}

	function next () {
		$this->position++;
	}

	function valid () {
		return $this->result->data_seek($this->position);
	}

	public function count () {
		return $this->getNumRecords();
	}

	function getFields () {
		return $this->result->fetch_fields();
	}

	function getValue () {
		if ($x = $this->result->fetch_array())
			return $x[0];
		return false;
	}

	function getList () {
		$this->rewind();
		$list = array();
		while ($x = $this->result->fetch_array())
			$list[] = $x[0];
		$this->rewind();
		return $list;
	}

	function getHash () {
		$hash = array();
		$this->rewind();
		while ($x = $this->result->fetch_array())
			$hash[$x[0]] = $x[1];
		return $hash;
	}

	function getObject () {
		return $this->result->fetch_object($this->dto);
	}

	function getObjects () {
		$objs = array();
		$this->rewind();
		while ($x = $this->result->fetch_object($this->dto))
			$objs[] = $x;
		return $objs;
	}

	public function getNumRecords () {
		return (empty($this->result->num_rows) ? 0 : $this->result->num_rows);
	}

}
