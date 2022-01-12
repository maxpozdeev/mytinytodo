<?php

/*
	This file is part of myTinyTodo.
	(C) Copyright 2021 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v2 license. See file COPYRIGHT for details.
*/

class Config
{
	/** @var bool */
	public static $noDatabase = false;

	/** @var array[] */
	private static $dbparams = array(
		# Database type: sqlite or mysql
		'db' => array('default'=>'sqlite', 'type'=>'s'),

		# Specify these settings if you selected above to use Mysql
		'mysql.host' => array('default'=>'localhost', 'type'=>'s'),
		'mysql.db' => array('default'=>'mytinytodo', 'type'=>'s'),
		'mysql.user' => array('default'=>'user', 'type'=>'s'),
		'mysql.password' => array('default'=>'', 'type'=>'s'),

		# Tables prefix
		'prefix' => array('default'=>'', 'type'=>'s'),

		# Use mysqli driver for mysql db. Will use PDO if set to 0.
		'mysqli' => array('default'=>1, 'type'=>'i')
	);

	/** @var array[] */
	public static $params = array(
		# These two parameters are used when mytinytodo index.php called not from installation directory
		# 'url' - URL where index.php is called from (ex.: http://site.com/todo.php)
		# 'mtt_url' - directory URL where mytinytodo is installed (with trailing slash) (ex.: http://site.com/lib/mytinytodo/)
		'url' => array('default'=>'', 'type'=>'s'),
		'mtt_url' => array('default'=>'', 'type'=>'s'),

		# Top title
		'title' => array('default'=>'', 'type'=>'s'),

		# Language pack
		'lang' => array('default'=>'en', 'type'=>'s'),

		# Password to protect your tasks from modification,
		# leave empty that everyone could read/write todolist
		'password' => array('default'=>'', 'type'=>'s'),

		# Smart Syntax enabled flag
		'smartsyntax' => array('default'=>1, 'type'=>'i'),

		# Default Time zone
		'timezone' => array('default'=>'UTC', 'type'=>'s'),

		# To disable auto adding selected tag set value to 0
		'autotag' => array('default'=>1, 'type'=>'i'),

		# duedate calendar format: 1 => y-m-d (default), 2 => m/d/y, 3 => d.m.y
		'duedateformat' => array('default'=>1, 'type'=>'i'),

		# First day of week: 0-Sunday, 1-Monday, 2-Tuesday, .. 6-Saturday
		'firstdayofweek' => array('default'=>1, 'type'=>'i'),

		# Date/time formats
		'clock' => array('default'=>24, 'type'=>'i', 'options'=>array(12,24)),
		'dateformat' => array('default'=>'j M Y', 'type'=>'s'),
		'dateformat2' => array('default'=>'n/j/y', 'type'=>'s'),
		'dateformatshort' => array('default'=>'j M', 'type'=>'s'),
		'template' => array('default'=>'default', 'type'=>'s'),

		# Show task date in list
		'showdate' => array('default'=>0, 'type'=>'i'),

		# Use Markdown syntax for notes. Set to 'v1' to use old v1.6 syntax.
		'markup' => array('default'=>'markdown', 'type'=>'s'),
	);

	/** @var mixed[] */
	private static $config;


	/**
	 *
	 * @param mixed[] $config
	 * @return void
	 */
	public static function loadDbConfig(array $config)
	{
		self::$config = $config;
	}

	/**
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function load()
	{
		if (self::$noDatabase) {
			return;
		}
		$j = self::requestDomain('config.json');
		foreach ($j as $key=>$val) {
			// Ignore params for database config
			if ( !isset(self::$dbparams[$key]) ) {
				self::$config[$key] = $val;
			}
		}
	}

	/**
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key)
	{
		if (isset(self::$config[$key])) return self::$config[$key];
		elseif (isset(self::$params[$key])) return self::$params[$key]['default'];
		elseif (isset(self::$dbparams[$key])) return self::$dbparams[$key]['default'];
		else return null;
	}

	/**
	 *
	 * @param string $key
	 * @return string|null
	 */
	public static function getUrl($key)
	{
		$url = '';
		if ( isset(self::$config[$key]) ) $url = self::$config[$key];
		else if( isset(self::$params[$key]) ) $url = self::$params[$key]['default'];
		else return null;
		return str_replace( ["\r","\n"], '', $url );
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 * @throws Exception
	 */
	public static function set($key, $value)
	{
		if ($key == "prefix" && $value !== "" && !preg_match("/^[a-zA-Z0-9_]+$/", $value)) {
			throw new Exception("Incorrect table prefix. Can contain only latin letters, digits and underscore character.");
		}
		self::$config[$key] = $value;
	}

	/**
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function saveDbConfig()
	{
		$s = '';
		foreach (self::$dbparams as $param => $v)
		{
			if ( !isset(self::$config[$param]) ) $val = $v['default'];
			elseif ( isset($v['options']) && !in_array(self::$config[$param], $v['options']) ) $val = $v['default'];
			else $val = self::$config[$param];
			if ($v['type']=='i') {
				$s .= "\$config['$param'] = ".(int)$val.";\n";
			}
			else {
				$s .= "\$config['$param'] = '".str_replace(array("\\","'"),array("\\\\","\\'"),$val)."';\n";
			}
		}
		$f = fopen(MTTPATH. 'db/config.php', 'w');
		if($f === false) throw new Exception("Error while saving config file");
		fwrite($f, "<?php\n\$config = array();\n$s?>");
		fclose($f);

		//Reset Zend OPcache
		//opcache_get_status() sometimes crashes
		//TODO: save config in database!
		if (function_exists("opcache_invalidate") && 0 != (int)opcache_get_configuration()["directives"]["opcache.enable"]) {
			opcache_invalidate(MTTPATH. 'db/config.php', true);
		}

	}

	/**
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function save()
	{
		$j = array();
		foreach (self::$params as $param => $v)
		{
			if ( !isset(self::$config[$param]) ) $val = $v['default'];
			elseif ( isset($v['options']) && !in_array(self::$config[$param], $v['options'])) $val = $v['default'];
			else $val = self::$config[$param];

			if ($v['type']=='i') $val = (int)$val;
			else $val = strval($val);

			$j[$param] = $val;
		}
		self::saveDomain('config.json', $j);
	}

	/**
	 *
	 * @param string $key
	 * @return array
	 * @throws Exception
	 */
	public static function requestDomain($key)
	{
		$db = DBConnection::instance();
		$json = $db->sq("SELECT param_value FROM {$db->prefix}settings WHERE param_key = ?", array($key));
		if (!$json) return array();
		$j = json_decode($json, true);
		return $j;
	}

	/**
	 *
	 * @param string $key
	 * @param array $array
	 * @return void
	 * @throws Exception
	 */
	public static function saveDomain($key, $array)
	{
		$json = json_encode($array, JSON_PRETTY_PRINT);
		$db = DBConnection::instance();
		$keyExists = $db->sq("SELECT COUNT(param_key) FROM {$db->prefix}settings WHERE param_key = ?", array($key) );
		if ($keyExists) {
			$db->ex("UPDATE {$db->prefix}settings SET param_value = ? WHERE param_key = ?", array($json,$key) );
		}
		else {
			$db->ex("INSERT INTO {$db->prefix}settings (param_key,param_value) VALUES (?,?)", array($key,$json) );
		}
	}
}

?>