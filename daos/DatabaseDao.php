<?php
/**
 * Common gateway for database queries and tools.
 *
 * @category Utility
 * @author Dan Wager
 * @version 1.0
 */
class DatabaseDao extends Dao {
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
	public function __construct ($host,$dbname,$user,$pass) {
		if (!defined('DATABASE_DATE_FORMAT'))
			define('DATABASE_DATE_FORMAT','Y-m-d');
		if (!defined('DATABASE_DATE_FIRST_FORMAT'))
			define('DATABASE_DATE_FIRST_FORMAT','Y-m-01');
		if (!defined('DATABASE_DATETIME_FORMAT'))
			define('DATABASE_DATETIME_FORMAT','Y-m-d H:i:s');
		$this->host = $host;
		$this->name = $dbname;
		$this->user = $user;
		$this->pass = $pass;
		$this->dbk = $this->host.$this->name;
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
			$mysqli = new mysqli($this->host,$this->user,$this->pass,$this->name);
			if (!($mysqli instanceof mysqli))
				throw new Exception('Could not connect to the database');
			DatabaseBox::setDbh($this->dbk,$mysqli);
		}
		return true;
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
	protected function query ($sql, $returnNewId=false) {
		if (!DatabaseBox::getDbh($this->dbk))
			$this->connect();
		$dbh = DatabaseBox::getDbh($this->dbk);
		if (!$result = $dbh->query($sql))
			throw new DevmoException('Error Querying Database: '.$dbh->errno.':'.$dbh->error.":\n".preg_replace('=\s+=',' ',$sql));
		if ($result instanceof mysqli_result)
			return new ResultSetDatabaseDao($result);
		if ($returnNewId)
			return DatabaseBox::getDbh($this->dbk)->insert_id;
		return true;
	}


	/**
	 * formatDate
	 *
	 * @access protected
	 * @param mixed $date
	 * @return void
	 */
	protected function formatDate ($date,$nullable=true,$first=false) {
		$date = trim($date);
		return $date
			? "'".DatabaseBox::getDbh($this->dbk)->real_escape_string(date(($first?DATABASE_DATE_FIRST_FORMAT:DATABASE_DATE_FORMAT),strtotime($date)))."'"
			: ($nullable?'NULL':false);
  }


	/**
	 * formatDateTime
	 *
	 * @access protected
	 * @param mixed $dateTime
	 * @return void
	 */
	protected function formatDateTime ($dateTime,$nullable=true) {
		$dateTime = trim($dateTime);
		return $dateTime
			? "'".DatabaseBox::getDbh($this->dbk)->real_escape_string(date(DATABASE_DATETIME_FORMAT,strtotime($dateTime)))."'"
			: ($nullable?'NULL':false);
  }


	/**
	 * formatInt
	 *
	 * @access protected
	 * @param mixed $int
	 * @return void
	 */
	protected function formatNumber ($int,$nullable=true) {
		$int = trim($int);
		return is_numeric($int)
			? DatabaseBox::getDbh($this->dbk)->real_escape_string(preg_replace('/[^0-9\-\.]*/','',$int))
			: ($nullable?'NULL':false);
  }


	/**
	 * formatVarchar
	 *
	 * @access protected
	 * @param mixed $varchar
	 * @return void
	 */
	protected function formatText ($varchar,$nullable=true) {
		$varchar = trim($varchar);
		return $varchar
			? "'".DatabaseBox::getDbh($this->dbk)->real_escape_string($varchar)."'"
			: ($nullable?'NULL':false);
  }


	/**
	 * formatMD5
	 *
	 * @access protected
	 * @param mixed $md5
	 * @return void
	 */
	protected function formatMD5 ($md5,$nullable=true) {
		return $md5
			? "'".md5($md5)."'"
			: ($nullable?'NULL':false);
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



class ResultSetDatabaseDao implements Iterator, Countable {
	private $result = null;
	private $position = 0;

	public function __construct ($result) {
		if (!($result instanceof mysqli_result))
			throw new InvalidDevmoException('DB Query Resource',$dbResource);
		$this->result = $result;
		$this->position = 0;
	}

	function __destruct () {
		$this->result->free_result();
	}

	function rewind () {
		$this->position = 0;
		if ($this->getNumRecords()>0)
			$this->result->data_seek($this->position);
	}

	function current () {
		return $this->result->fetch_object();
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

	function getValue () {
		if ($x = $this->result->fetch_array())
			return $x[0];
		return false;
	}

	function getList () {
		$this->rewind();
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
		return $this->result->fetch_object();
	}

	function getObjects () {
		$objs = array();
		$this->rewind();
		while ($x = $this->result->fetch_object())
			$objs[] = $x;
		return $objs;
	}

	public function getNumRecords () {
		return (empty($this->result->num_rows) ? 0 : $this->result->num_rows);
	}

}
