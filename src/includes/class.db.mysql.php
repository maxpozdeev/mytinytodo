<?php

/*
	(C) Copyright 2021 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v2 license. See file COPYRIGHT for details.
*/

// ---------------------------------------------------------------------------- //
class DatabaseResult_Mysql
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

// ---------------------------------------------------------------------------- //
class Database_Mysql
{
	private $dbh;
	private $affected = null;
	var $lastQuery;
	private $dbname;
	var $prefix = '';

	function __construct()
	{
	}

	function connect($host, $user, $pass, $db)
	{
		$options = array(
			PDO::MYSQL_ATTR_FOUND_ROWS => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);
		$this->dbname = $db;

		$this->dbh = new PDO("mysql:host=$host;dbname=$db", $user, $pass, $options);
		return true;
	}



	/*
		Returns single row of SELECT query as indexed array (FETCH_NUM).
		Returns single field value if resulting array has only one field.
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
		Returns single row of SELECT query as dictionary array (FETCH_ASSOC).
		Returns single field value if resulting array has only one field.
	*/
	function sqa($query, $p = NULL)
	{
		$q = $this->_dq($query, $p);

		$res = $q->fetch_assoc();
		if ($res === false) return NULL;

		if (sizeof($res) > 1) return $res;
		else return $res[0];
	}

	function dq($query, $p = NULL)
	{
		return $this->_dq($query, $p);
	}

	/*
		for resultless queries like INSERT,UPDATE,DELETE
	*/
	function ex($query, $p = NULL)
	{
		return $this->_dq($query, $p, true);
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
		$dbr = new DatabaseResult_Mysql($this->dbh, $query, $resultless);
		$this->affected = $dbr->rowsAffected();
		return $dbr;
	}

	function affected()
	{
		return $this->affected;
	}

	function quote($s)
	{
		return '\''. addslashes($s). '\'';
	}

	function quoteForLike($format, $s)
	{
		$s = str_replace(array('%','_'), array('\%','\_'), addslashes($s));
		return '\''. sprintf($format, $s). '\'';
	}

	function last_insert_id()
	{
		return $this->dbh->lastInsertId();
	}

	function table_exists($table)
	{
		$r = $this->sq("SELECT 1 FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
						array($this->dbname, $table) );
		if ($r === false || $r === null) return false;
		return true;
	}
}

?>