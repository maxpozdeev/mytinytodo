<?php
/*
	This file is part of myTinyTodo.
	(C) Copyright 2009-2010,2020 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v2 license. See file COPYRIGHT for details.
*/

require_once('./init.php');

//Parse query string
if ( isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '' )
{
	parse_str($_SERVER['QUERY_STRING'], $q);
	if (isset($q['list'])) {
		$hash = ($q['list'] == 'alltasks') ? ['alltasks'] : ['list', (int)$q['list']];
		unset($q['list']);
		redirectWithHashRoute($q, $hash);
	}
}


$lang = Lang::instance();

if ($lang->rtl()) {
	Config::set('rtl', 1);
}

if (!is_int(Config::get('firstdayofweek')) || Config::get('firstdayofweek')<0 || Config::get('firstdayofweek')>6) {
	Config::set('firstdayofweek', 1);
}

if ( !isset($_GET['mobile']) && !isset($_GET['pda']) && !isset($_GET['desktop']) && Config::get('detectmobile') ) {
	if (is_mobile()) {
		Config::set('mobile', 1);
	}
}
//TODO: if we have a desktop or mobile theme request we have to save it in cookies and redirect (if auto-detect mobiles is on)
else if ( isset($_GET['desktop']) ) { //more priority than mobile
	Config::set('mobile', 0);
}
else if ( isset($_GET['mobile']) || isset($_GET['pda']) ) {
	Config::set('mobile', 1);
}

define('TEMPLATEPATH', MTTTHEMES. Config::get('template'). '/');

require(TEMPLATEPATH. 'index.php');

// end

function redirectWithHashRoute(array $q, array $hash)
{
	$url = get_unsafe_mttinfo('url');
	$query = http_build_query($q);
	if ($query != '') $url .= "?$query";
	if (count($hash) > 0) {
		$encodedHash = implode("/", array_map("rawurlencode", $hash));
		$url .= "#$encodedHash";
	}
	header("Location: ". $url);
	exit;
}

function is_mobile()
{	//basic detection
	if (preg_match("/(iphone|ipad|android)/i", _server('HTTP_USER_AGENT'))) {
		return true;
	}
	return false;
}

function getDesktopUrl($escape = true)
{
	$url = (Config::get('detectmobile') && is_mobile()) ? get_unsafe_mttinfo('desktop_url') : get_unsafe_mttinfo('url');
	return $escape ? htmlspecialchars($url) : $url;
}