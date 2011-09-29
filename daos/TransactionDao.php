<?php
abstract class TransactionDao extends DatabaseDao {
	public function begin() {
		if ($dbh = DatabaseBox::getDbh($this->dbk)) {
			$dbh->autocommit(false);
		}
	}
	public function commit() {
		if ($dbh = DatabaseBox::getDbh($this->dbk)) {
			$dbh->commit();
			$dbh->autocommit(true);
		}
	}
	public function rollback() {
		if ($dbh = DatabaseBox::getDbh($this->dbk)) {
			$dbh->rollback();
			$dbh->autocommit(true);
		}
	}
	public function transaction($callback) {
		$result = false;
		if (is_callable($callback)) {
			try {
				$this->begin();
				$result = ($callback() ?: true);
				$this->commit();
			} catch (Exception $e) {
				$this->rollback();
			}
		}
		return $result;
	}
}
