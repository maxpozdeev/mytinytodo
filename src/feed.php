<?php

/*
	This file is part of myTinyTodo.
	(C) Copyright 2009 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/

$dontStartSession = 1;
require_once('./init.php');
require_once('./lang/class.default.php');
require_once('./lang/'.Config::get('lang').'.php'); 
$lang = new Lang(); 

$listId = (int)_get('list');

$listData = $db->sqa("SELECT * FROM lists WHERE id=$listId");
if($needAuth && (!$listData || !$listData['published'])) {
	die("Access denied!<br> List is not published.");
}
if(!$listData) {
	die("No such list");
}

$listData['_feed_title'] = sprintf($lang->get('feed_title'), $listData['name']);
$listData['_feed_descr'] = sprintf($lang->get('feed_description'), $listData['name']);
htmlarray_ref($listData);

$data = array();
$q = $db->dq("SELECT * FROM todolist WHERE list_id=$listId ORDER BY d_created DESC LIMIT 100");
while($r = $q->fetch_assoc($q)) 
{
	if($r['prio'] > 0) $r['prio'] = '+'.$r['prio'];
	$a = array();
	if($r['prio']) $a[] = $lang->get('priority'). ": $r[prio]";
	if($r['duedate'] != '') {
		$ad = explode('-', $r['duedate']);
		$a[] = $lang->get('due'). ": ".formatDate3(Config::get('dateformat'), (int)$ad[0], (int)$ad[1], (int)$ad[2], $lang);
	}
	if($r['tags'] != '') $a[] = $lang->get('tags'). ": ". str_replace(',', ', ', $r['tags']);
	$r['_descr'] = nl2br($r['note']). ($a && $r['note']!='' ? "<br><br>" : "").  implode("<br>", $a);
	$data[] = htmlarray($r);
}

printRss($listData, $data);


function printRss($listData, $data)
{
	$link = htmlarray('http://'. $_SERVER['HTTP_HOST']. substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1));
	$buildDate = gmdate('r');

	$s = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n<channel>\n".
		"<title>$listData[_feed_title]</title>\n<link>$link</link>\n<description>$listData[_feed_descr]</description>\n".
		"<lastBuildDate>$buildDate</lastBuildDate>\n\n";

	foreach($data as $v)
	{
		$d = gmdate('r', $v['d_created']);
		$guid = $listData['id'].'-'.$v['id'].'-'.$v['d_created'];

		$s .= "<item>\n<title>$v[title]</title>\n".
			"<link>$link</link>\n".
			"<pubDate>$d</pubDate>\n".
			"<description>$v[_descr]</description>\n".
			"<guid isPermaLink=\"false\">$guid</guid>\n".
			"</item>\n";
	}

	$s .= "</channel>\n</rss>";

	header("Content-type: text/xml");
	print $s;
}

?>