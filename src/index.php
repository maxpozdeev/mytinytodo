<?php
/*
	This file is part of myTinyTodo.
	(C) Copyright 2009 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/


require_once('init.php');
require_once('./lang/class.default.php');
require_once('./lang/'.Config::get('lang').'.php');

$lang = new Lang();

if($lang->rtl()) Config::set('rtl', 1);

if(!is_int(Config::get('firstdayofweek')) || Config::get('firstdayofweek')<0 || Config::get('firstdayofweek')>6) Config::set('firstdayofweek', 1);

$_mttinfo = array();

define('TEMPLATEPATH', './themes/'.Config::get('template').'/');

require(TEMPLATEPATH. 'index.php');


function _e($s)
{
	global $lang;
	echo $lang->get($s);
}

function mttinfo($v)
{
	global $_mttinfo;
	if(!isset($_mttinfo[$v])) {
		echo get_mttinfo($v);
	} else {
		echo $_mttinfo[$v];
	}
}

function get_mttinfo($v)
{
	global $_mttinfo, $lang;
	if(isset($_mttinfo[$v])) return $_mttinfo[$v];
	switch($v)
	{
		case 'template_uri':
			$_mttinfo['template_uri'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH). 'themes/'. Config::get('template') . '/';
			return $_mttinfo['template_uri'];
		case 'template_url':
			$_mttinfo['template_url'] = get_mttinfo('siteurl'). 'themes/'. Config::get('template') . '/';
			return $_mttinfo['template_url'];
		case 'siteurl':
			$_mttinfo['siteurl'] = 'http://'.$_SERVER['HTTP_HOST'] .($_SERVER['SERVER_PORT'] != 80 ? ':'.$_SERVER['SERVER_PORT'] : ''). parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
			return $_mttinfo['siteurl'];
		case 'title':
			$_mttinfo['title'] = (Config::get('title') != '') ? htmlarray(Config::get('title')) : $lang->get('My Tiny Todolist');
			return $_mttinfo['title'];
	}
}

?>