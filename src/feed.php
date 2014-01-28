<?php

/*
	This file is part of myTinyTodo.
	(C) Copyright 2009-2010 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v2 license. See file COPYRIGHT for details.
*/

$dontStartSession = 1;
require_once('./init.php');

$lang = Lang::instance();

$listId = (int)_get('list');

$listData = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$listId");
if($needAuth && (!$listData || !$listData['published'])) {
	die("Access denied!<br> List is not published.");
}
if(!$listData) {
	die("No such list");
}

$feedType = _get('feed');
$sqlWhere = '';
if($feedType == 'completed') {
    $listData['_uid_field'] = 'd_completed';
    $listData['_feed_descr'] = $lang->get('feed_completed_tasks');
    $sqlWhere = 'AND compl=1';
}
elseif($feedType == 'modified') {
    $listData['_uid_field'] = 'd_edited';
    $listData['_feed_descr'] = $lang->get('feed_modified_tasks');
}
elseif($feedType == 'current') {
	$listData['_uid_field'] = 'd_created';
	$listData['_feed_descr'] = $lang->get('feed_new_tasks');
	$sqlWhere = 'AND compl=0';
}
else {
    $listData['_uid_field'] = 'd_created';
    $listData['_feed_descr'] = $lang->get('feed_new_tasks');
}

$listData['_feed_title'] = sprintf($lang->get('feed_title'), $listData['name']) . ' - '. $listData['_feed_descr'];
htmlarray_ref($listData);

$data = array();
$q = $db->dq("SELECT * FROM {$db->prefix}todolist WHERE list_id=$listId $sqlWhere ORDER BY ". $listData['_uid_field'] ." DESC LIMIT 100");
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
	if($r['compl']) $a[] = $lang->get('taskdate_completed'). ": ". timestampToDatetime($r['d_completed']);
	$r['title'] = strip_tags($r['title']);
	$r['note'] = escapeTags($r['note']);
	$r['_descr'] = nl2br($r['note']). ($a && $r['note']!='' ? "<br/><br/>" : "").  implode("<br/>", htmlarray($a));
	$data[] = $r;
}

printRss($listData, $data);


function printRss($listData, $data)
{
	$link = get_mttinfo('url'). "?list=". $listData['id'];
	$buildDate = gmdate('r');

	$s = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n<channel>\n".
		"<title>$listData[_feed_title]</title>\n<link>$link</link>\n<description>$listData[_feed_descr]</description>\n".
		"<lastBuildDate>$buildDate</lastBuildDate>\n\n";

	foreach($data as $v)
	{
		$d = gmdate('r', $v[$listData['_uid_field']]);
		$guid = $listData['id'].'-'.$v['id'].'-'.$v[$listData['_uid_field']];

		$s .= "<item>\n<title><![CDATA[". str_replace("]]>", "]]]]><![CDATA[>", $v['title']). "]]></title>\n".
			"<link>$link</link>\n".
			"<pubDate>$d</pubDate>\n".
			"<description><![CDATA[". $v['_descr']. "]]></description>\n".
			"<guid isPermaLink=\"false\">$guid</guid>\n".
			"</item>\n";
	}

	$s .= "</channel>\n</rss>";

	header("Content-type: text/xml; charset=utf-8");
	print $s;
}

?>