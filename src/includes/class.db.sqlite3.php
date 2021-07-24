<?php

/*
	(C) Copyright 2009,2021 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v2 license. See file COPYRIGHT for details.
*/

class DatabaseResult_Sqlite3
{
	private $q;
	private $affected;

	function __construct($dbh, $query, $resultless = 0)
	{
		// use with DELETE, INSERT, UPDATE
		if ($resultless)
		{
			$this->affected = $dbh->exec($query); //throws PDOException
		}
		// SELECT
		else
		{
			$this->q = $dbh->query($query); //throws PDOException
			$this->affected = $this->q->rowCount();
		}
	}

	function fetch_row()
	{
		return $this->q->fetch(PDO::FETCH_NUM);
	}

	function fetch_assoc()
	{
		return $this->q->fetch(PDO::FETCH_ASSOC);
	}

	function rowsAffected()
	{
		return $this->affected;
	}

}

class Database_Sqlite3
{
	private $dbh;
	private $affected = null;
	var $lastQuery;

	function __construct()
	{
	}

	function connect($filename)
	{
		$options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);
		$this->dbh = new PDO("sqlite:$filename", null, null, $options); //throws PDOException
		return true;
	}

	/*
		SELECT queries for single row
	*/
	function sq($query, $p = NULL)
	{
		$q = $this->_dq($query, $p);

		$res = $q->fetch_row();
		if ($res === false) return NULL;

		if (sizeof($res) > 1) return $res;
		else return $res[0];
	}

	/*
		SELECT queries for single row
	*/
	function sqa($query, $p = NULL)
	{
		$q = $this->_dq($query, $p);

		$res = $q->fetch_assoc();
		if ($res === false) return NULL;
		return $res;
	}

	/*
		SELECT queries for multiple rows
	*/
	function dq($query, $p = NULL)
	{
		return $this->_dq($query, $p);
	}

	/*
		for resultless queries like INSERT,UPDATE,DELETE
	*/
	function ex($query, $p = NULL)
	{
		$dbr = $this->_dq($query, $p, 1);
		return $this->affected();
	}

	private function _dq($query, $p = NULL, $resultless = 0)
	{
		if (!isset($p)) $p = array();
		elseif (!is_array($p)) $p = array($p);

		$m = explode('?', $query);

		if (sizeof($p) > 0)
		{
			if (sizeof($m) < sizeof($p)+1) {
				throw new Exception("params to set MORE than query params");
			}
			if (sizeof($m) > sizeof($p)+1) {
				throw new Exception("params to set LESS than query params");
			}
			$query = "";
			for ($i=0; $i<sizeof($m)-1; $i++) {
				$query .= $m[$i]. (is_null($p[$i]) ? 'NULL' : $this->quote($p[$i]));
			}
			$query .= $m[$i];
		}
		$this->lastQuery = $query;
		$dbr = new DatabaseResult_Sqlite3($this->dbh, $query, $resultless);
		$this->affected = $dbr->rowsAffected();
		return $dbr;
	}

	function affected()
	{
		return $this->affected;
	}

	function quote($s)
	{
		return $this->dbh->quote($s);
	}

	function quoteForLike($format, $s)
	{
		$s = str_replace(array('\\','%','_'), array('\\\\','\%','\_'), $s);
		return $this->dbh->quote(sprintf($format, $s)). " ESCAPE '\'";
	}

	function last_insert_id()
	{
		return $this->dbh->lastInsertId();
	}

	function table_exists($table)
	{
		$exists = $this->sq("SELECT 1 FROM sqlite_master WHERE type='table' AND name=?", $table);
		if ($exists == "1") {
		  return true;
		}
		$exists = $this->sq("SELECT 1 FROM sqlite_temp_master WHERE type='table' AND name=?", $table);
		if ($exists == "1") {
		  return true;
		}
		return false;
	}
}

?>