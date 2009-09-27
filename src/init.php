<?php

require_once('common.php');
require_once('./db/config.php');

ini_set('display_errors', 'On');

# MySQL Database Connection
if($config['db'] == 'mysql')
{
	require_once('class.db.mysql.php');
	$db = new Database_Mysql;
	$db->connect($config['mysql.host'], $config['mysql.user'], $config['mysql.password'], $config['mysql.db']);
	$db->dq("SET NAMES utf8");
}

# SQLite3 (pdo_sqlite)
else
{
	require_once('class.db.sqlite3.php');
	$db = new Database_Sqlite3;
	$db->connect('./db/todolist.db');
}


$needAuth = (isset($config['password']) && $config['password'] != '') ? 1 : 0;
if($needAuth)
{
	if(isset($config['session']) && $config['session'] == 'files')
	{
		session_save_path(realpath('./tmp/sessions/'));
		ini_set('session.gc_maxlifetime', '1209600'); # 14 days session file minimum lifetime
		ini_set('session.gc_probability', 1);
		ini_set('session.gc_divisor', 10);
	}

	ini_set('session.use_cookies', true);
	ini_set('session.use_only_cookies', true);
	session_set_cookie_params(1209600, substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1)); #14 days session cookie lifetime
	session_start();
}


function canAllRead()
{
	global $config;
	if(!isset($config['password']) || $config['password'] == '') return true;
	if(isset($config['allowread']) && $config['allowread']) return true;
	return false;
}

function is_logged()
{
	if(!isset($_SESSION['logged']) || !$_SESSION['logged']) return false;
	return true;
}

?>