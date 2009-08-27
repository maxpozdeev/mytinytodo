<?php

class DatabaseResult_Sqlite
{
	private  $parent;
	private $q;
	var $query;
	private $rows = NULL;
	private $affected = NULL;

	function __construct($query, &$h, $resultless = 0)
	{
		$this->parent = $h;
		$this->query = $query;

		if($resultless) $this->q = @$this->parent->dbh->queryExec($query, $error);
		else $this->q = @$this->parent->dbh->query($query, 0, $error);

		if(!$this->q)
		{
			if($error) throw new Exception($error);
			else throw new Exception(sqlite_error_string($this->parent->dbh->lastError()));
		}
	}

	function rows()
	{
		if (!is_null($this->rows)) return $this->rows;
		$this->rows = $this->q->numRows();
		return $this->rows;
	}

	function fetch_row()
	{
		return $this->q->fetch(SQLITE_NUM);
	}

	function fetch_assoc()
	{
		return $this->q->fetch(SQLITE_ASSOC);
	}

}

class Database_Sqlite
{
	var $dbh;
	private $affected = null;

	function __construct()
	{
	}

	function connect($filename, $mode=0666)
	{
		try {
			$this->dbh = new SQLiteDatabase($filename, 0666,  $error);
		}
		catch(Exception $e) {
			throw new Exception($error);
		}
		return true;
	}

	function sq($query, $p = NULL)
	{
		$q = $this->_dq($query, $p);

		if($q->rows()) $res = $q->fetch_row();
		else return NULL;

		if(sizeof($res) > 1) return $res;
		else return $res[0];
	}

	function sqa($query, $p = NULL)
	{
		$q = $this->_dq($query, $p);

		if($q->rows()) $res = $q->fetch_assoc();
		else return NULL;

		if(sizeof($res) > 1) return $res;
		else return $res[0];
	}
	
	function dq($query, $p = NULL)
	{
		return $this->_dq($query, $p);
	}

	/* 
		for resultless queries like INSERT,UPDATE
	*/
	function ex($query, $p = NULL)
	{
		return $this->_dq($query, $p, 1);
	}

	private function _dq($query, $p = NULL, $resultless = 0)
	{
		if(!isset($p)) $p = array();
		elseif(!is_array($p)) $p = array($p);

		$m = explode('?', $query);

		if(sizeof($p)>0)
		{
			if(sizeof($m)< sizeof($p)+1) {
				throw new Exception("params to set MORE than query params");
			}
			if(sizeof($m)> sizeof($p)+1) {
				throw new Exception("params to set LESS than query params");
			}
			$query = "";
			for($i=0; $i<sizeof($m)-1; $i++) {
				$query .= $m[$i]. $this->quote($p[$i]);
			}
			$query .= $m[$i];
		}
		return new DatabaseResult_Sqlite($query, $this, $resultless);
	}

	function affected()
	{
		if(is_null($this->affected))
		{
			$this->affected = $this->dbh->changes();
		}
		return $this->affected;
	}

	function quote($s)
	{
		return '\''. sqlite_escape_string($s). '\'';
	}

	function quoteForLike($format, $s)
	{
		$s = str_replace(array('\\','%','_'), array('\\\\','\%','\_'), $s);
		return $this->quote(sprintf($format, $s));
	}

	function last_insert_id()
	{
		return $this->dbh->lastInsertRowid();
	}

	function table_exists($table)
	{
		$table = $this->quote($table);
		$q = @$this->dbh->query("SELECT 1 FROM $table WHERE 1=0");
		if($q === false) return false;
		else return true;
	}
}

?>