<?php


class DBConnection
{
	protected static $instance;

	public static function init(Database_Abstract $instance) : Database_Abstract
	{
		self::$instance = $instance;
		return $instance;
	}

	public static function instance() : Database_Abstract
	{
        if (!isset(self::$instance)) {
			throw new Exception("DBConnection is not initialized");
        }
		return self::$instance;
	}

	public static function setPrefix($prefix)
	{
		$db = self::instance();
		$db->prefix = $prefix;
	}
}

abstract class Database_Abstract
{
	var $lastQuery = null;
	var $prefix = '';
	abstract function connect($params);
	abstract function sq($query, $p = NULL);
	abstract function sqa($query, $p = NULL);
	abstract function dq($query, $p = NULL) : DatabaseResult_Abstract;
	abstract function ex($query, $p = NULL);
	abstract function affected();
	abstract function quote($s);
	abstract function quoteForLike($format, $s);
	abstract function lastInsertId($name = null);
	abstract function tableExists($table);
}

abstract class DatabaseResult_Abstract
{
	abstract function fetchRow();
	abstract function fetchAssoc();
}

?>