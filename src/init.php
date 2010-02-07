<?php

if(!defined('MTTPATH')) define('MTTPATH', dirname(__FILE__) .'/');

require_once(MTTPATH. 'common.php');
require_once(MTTPATH. 'db/config.php');

ini_set('display_errors', 'On');

if(!isset($config)) global $config;
Config::loadConfig($config);
unset($config);

# MySQL Database Connection
if(Config::get('db') == 'mysql')
{
	require_once(MTTPATH. 'class.db.mysql.php');
	$db = new Database_Mysql;
	$db->connect(Config::get('mysql.host'), Config::get('mysql.user'), Config::get('mysql.password'), Config::get('mysql.db'));
	$db->dq("SET NAMES utf8");
}

# SQLite3 (pdo_sqlite)
else
{
	require_once(MTTPATH. 'class.db.sqlite3.php');
	$db = new Database_Sqlite3;
	$db->connect(MTTPATH. 'db/todolist.db');
}
$db->prefix = Config::get('prefix');

require_once(MTTPATH. 'lang/class.default.php');
require_once(MTTPATH. 'lang/'.Config::get('lang').'.php');

$needAuth = (Config::get('password') != '') ? 1 : 0;
if($needAuth && !isset($dontStartSession))
{
	if(Config::get('session') == 'files')
	{
		session_save_path(MTTPATH. 'tmp/sessions/');
		ini_set('session.gc_maxlifetime', '1209600'); # 14 days session file minimum lifetime
		ini_set('session.gc_probability', 1);
		ini_set('session.gc_divisor', 10);
	}

	ini_set('session.use_cookies', true);
	ini_set('session.use_only_cookies', true);
	session_set_cookie_params(1209600, url_dir(Config::get('url')=='' ? $_SERVER['REQUEST_URI'] : Config::get('url'))); # 14 days session cookie lifetime
	session_name('mtt-session');
	session_start();
}

function is_logged()
{
	if(!isset($_SESSION['logged']) || !$_SESSION['logged']) return false;
	return true;
}

function timestampToDatetime($timestamp, $tz)
{
	$format = Config::get('dateformat') .' '. (Config::get('clock') == 12 ? 'g:i A' : 'H:i');
	return formatTime($format, $timestamp, $tz);
}

function formatTime($format, $timestamp=0, $tz=null)
{
	$lang = Lang::instance();
	if($timestamp == 0) $timestamp = time();
	if(is_null($tz)) $tz = round(date('Z')/60);
	$newformat = strtr($format, array('F'=>'%1', 'M'=>'%2'));
	$adate = explode(',', gmdate('n,'.$newformat, $timestamp + $tz*60), 2);
	$s = $adate[1];
	if($newformat != $format)
	{
		$am = (int)$adate[0];
		$ml = $lang->get('months_long');
		$ms = $lang->get('months_short');
		$F = $ml[$am-1];
		$M = $ms[$am-1];
		$s = strtr($s, array('%1'=>$F, '%2'=>$M));
	}
	return $s;
}

?>