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

function js_options()
{
	$a = array(
		"title" => get_unsafe_mttinfo('title'),
		"lang" => Lang::instance()->jsStrings(),
		"mttUrl" => get_mttinfo('mtt_url'),
		"homeUrl" => get_mttinfo('url'),
		"needAuth" => need_auth() ? true : false,
		"isLogged" => is_logged() ? true : false,
		"showdate" => Config::get('showdate') ? true : false,
		"duedatepickerformat" => htmlspecialchars(Config::get('dateformat2')),
		"firstdayofweek" => (int) Config::get('firstdayofweek'),
		"calendarIcon" => get_mttinfo('template_url'). 'images/calendar.svg',
		"autotag" => Config::get('autotag') ? true : false,
		"markdown" => Config::get('markup') == 'v1' ? false : true
	);
	echo json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
