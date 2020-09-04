<?php

// Deprecated! Will be removed in the next major version!

if(!defined('MTTPATH')) define('MTTPATH', dirname(__FILE__) .'/');
if(!defined('MTTINC'))  define('MTTINC', MTTPATH. 'includes/');

require_once(MTTPATH. 'db/config.php');

if(isset($_GET['lang']) && preg_match("/^[a-z-]+$/i", $_GET['lang']) && file_exists(MTTPATH. 'lang/'. $_GET['lang']. '.php')) {
	$config['lang'] = $_GET['lang'];
}

if ( isset($_GET['json']) || isset($_GET['jsonfile']) ) {
	require_once(MTTPATH. 'lang/class.default.php');
	require_once(MTTPATH. 'lang/'. $config['lang']. '.php');
	header('Content-type: application/json; charset=utf-8');
	if ( isset($_GET['jsonfile']) ) {
		header('Content-disposition: attachment; filename='. urlencode($config['lang']). '.json');
	}
	echo Lang::instance_deprecated()->json('lang/'. $config['lang']. '.php', 1);
	exit;
}

require_once(MTTINC. 'class.lang.php');
Lang::loadLang($config['lang']);

header('Content-type: text/javascript; charset=utf-8');
echo "mytinytodo.lang.init(". Lang::instance()->makeJS(1) .");";

