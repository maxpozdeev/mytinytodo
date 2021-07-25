<?php

/*
	This file is part of myTinyTodo.
	(C) Copyright 2021 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v2 license. See file COPYRIGHT for details.
*/

class Config
{
	public static $params = array(
		'db' => array('default'=>'sqlite', 'type'=>'s'),
		'mysql.host' => array('default'=>'localhost', 'type'=>'s'),
		'mysql.db' => array('default'=>'mytinytodo', 'type'=>'s'),
		'mysql.user' => array('default'=>'user', 'type'=>'s'),
		'mysql.password' => array('default'=>'', 'type'=>'s'),
		'prefix' => array('default'=>'', 'type'=>'s'),
		'url' => array('default'=>'', 'type'=>'s'),
		'mtt_url' => array('default'=>'', 'type'=>'s'),
		'title' => array('default'=>'', 'type'=>'s'),
		'lang' => array('default'=>'en', 'type'=>'s'),
		'password' => array('default'=>'', 'type'=>'s'),
		'smartsyntax' => array('default'=>1, 'type'=>'i'),
		'timezone' => array('default'=>'UTC', 'type'=>'s'),
		'autotag' => array('default'=>1, 'type'=>'i'),
		'duedateformat' => array('default'=>1, 'type'=>'i'),
		'firstdayofweek' => array('default'=>1, 'type'=>'i'),
		'session' => array('default'=>'files', 'type'=>'s', 'options'=>array('files','default')),
		'clock' => array('default'=>24, 'type'=>'i', 'options'=>array(12,24)),
		'dateformat' => array('default'=>'j M Y', 'type'=>'s'),
		'dateformat2' => array('default'=>'n/j/y', 'type'=>'s'),
		'dateformatshort' => array('default'=>'j M', 'type'=>'s'),
		'template' => array('default'=>'default', 'type'=>'s'),
		'showdate' => array('default'=>0, 'type'=>'i'),
		'markup' => array('default'=>'markdown', 'type'=>'s'),
		'mysqli' => array('default'=>1, 'type'=>'i')
	);

	public static $config;

	public static function loadConfig($config)
	{
		self::$config = $config;
	}

	public static function get($key)
	{
		if(isset(self::$config[$key])) return self::$config[$key];
		elseif(isset(self::$params[$key])) return self::$params[$key]['default'];
		else return null;
	}

	public static function getUrl($key)
	{
		$url = '';
		if ( isset(self::$config[$key]) ) $url = self::$config[$key];
		else if( isset(self::$params[$key]) ) $url = self::$params[$key]['default'];
		else return null;
		return str_replace( ["\r","\n"], '', $url );
	}

	public static function set($key, $value)
	{
		self::$config[$key] = $value;
	}

	public static function save()
	{
		$s = '';
		foreach(self::$params as $param=>$v)
		{
			if(!isset(self::$config[$param])) $val = $v['default'];
			elseif(isset($v['options']) && !in_array(self::$config[$param], $v['options'])) $val = $v['default'];
			else $val = self::$config[$param];
			if($v['type']=='i') {
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
}

?>