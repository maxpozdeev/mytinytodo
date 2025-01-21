<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2011,2020-2021 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

$dontStartSession = 1;
require_once('./init.php');
require_once(MTTINC. 'markup.php');

$lang = Lang::instance();

$listId = (int)_get('list');
$db = DBConnection::instance();
$listData = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$listId");
if ( $listData && need_auth() && !$listData['published'] ) {
    $extra = json_decode($listData['extra'] ?? '', true, 10, JSON_INVALID_UTF8_SUBSTITUTE);
    $feedKey = (string) ($extra['feedKey'] ?? '');
    $inFeedKey = trim(_get('key'));
    if ($feedKey == '' || $feedKey != $inFeedKey) {
        die("Access denied!<br> List is not published.");
    }
}
if (!$listData) {
    die("No list found.");
}

$data = array();
$feedType = _get('feed');

if($feedType == 'completed') {
    $listData['_feed_descr'] = $lang->get('feed_completed_tasks');
    fillData( $data, $listId, 'd_completed', 'compl=1' );
}
elseif($feedType == 'modified') {
    $listData['_feed_descr'] = $lang->get('feed_modified_tasks');
    fillData( $data, $listId, 'd_edited', '' );
}
elseif($feedType == 'current') {
    $listData['_feed_descr'] = $lang->get('feed_new_tasks');
    fillData( $data, $listId, 'd_created', 'compl=0' );
}
elseif($feedType == 'status') {
    $listData['_feed_descr'] = $lang->get('feed_tasks');
    fillData( $data, $listId, 'd_created', '' );
    fillData( $data, $listId, 'd_edited', 'compl=0 AND d_edited > d_created' );
    fillData( $data, $listId, 'd_completed', 'compl=1' );
}
else {
    $listData['_feed_descr'] = $lang->get('feed_new_tasks');
    $feedType = 'tasks';
    fillData( $data, $listId, 'd_created', '' );
}

$listData['_feed_title'] = sprintf($lang->get('feed_title'), $listData['name']) . ' - '. $listData['_feed_descr'];
$listData['_feed_link'] = get_mttinfo('mtt_url'). "feed.php?list=". (int)$listData['id'] . ($feedType != '' ? "&feed=". $feedType : '');
$listData['_feed_type'] = $feedType;
htmlarray_ref($listData);

printRss($data, $listData);


function fillData(array &$data, int $listId, string $field, string $sqlWhere )
{
    $tasks = DBCore::default()->getTasksByListId($listId, $sqlWhere, "$field DESC", 100);
    $lang = Lang::instance();
    foreach ($tasks as $r)
    {
        if ($r['prio'] > 0) {
            $r['prio'] = '+'.$r['prio'];
        }
        $a = array(); //for _descr
        $a[] = $lang->get('task'). ": ". $r['title'];
        if ($r['prio']) {
            $a[] = $lang->get('priority'). ": $r[prio]";
        }
        if ($r['duedate'] != '') {
            $ad = explode('-', $r['duedate']);
            $a[] = $lang->get('due'). ": ".formatDate3(Config::get('dateformat'), (int)$ad[0], (int)$ad[1], (int)$ad[2], $lang);
        }
        if ($r['tags'] != '') {
            $a[] = $lang->get('tags'). ": ". str_replace(',', ', ', $r['tags']);
        }
        if ($r['compl']) {
            $a[] = $lang->get('taskdate_completed'). ": ". timestampToDatetime($r['d_completed']);
        }
        $r['title'] = htmlspecialchars( $r['title'] );
        $r['note'] = noteMarkup($r['note'], true);
        $r['_descr'] = implode("<br/>", htmlarray($a)). "<br/><br/>". $r['note'];
        $r['_title'] = "#". (int)$r['id']. ": ". $r['title'];
        $r['_d'] =  gmdate('r', $r[$field]);
        $r['_field'] = $field;
        $data[] = $r;
    }
}

function printRss(array $data, array $listData)
{
    $lang = Lang::instance();
    $link = get_mttinfo('url'). "?list=". (int)$listData['id'];
    $buildDate = gmdate('r');

    $s = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
        "<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n".
        "<channel>\n".
        "<title>$listData[_feed_title]</title>\n".
        "<link>$link</link>\n".
        "<atom:link href=\"{$listData['_feed_link']}\" rel=\"self\" type=\"application/rss+xml\"/>\n".
        "<description>$listData[_feed_descr]</description>\n".
        "<lastBuildDate>$buildDate</lastBuildDate>\n\n";

    foreach($data as $v)
    {
        $guid = $listData['_feed_type']. '-'. $listData['id']. '-'. $v['id']. '-'. $v[$v['_field']];
        $itemLink = $link. "&amp;task=". (int)$v['id'];

        $status = '';
        if ( $listData['_feed_type'] == 'status' ) {
            if ( $v['_field'] == 'd_created' ) {
                $status = $lang->get('feed_status_new');
            }
            elseif ( $v['_field'] == 'd_edited' ) {
                $status = $lang->get('feed_status_updated');
            }
            elseif ( $v['_field'] == 'd_completed' ) {
                $status = $lang->get('feed_status_completed');
            }
        }
        if ( $status !='' ) $status = "[$status] ";

        $s .= "\t<item>\n".
            "\t\t<title>". $status. $v['title']. "</title>\n".
            "\t\t<link>". $itemLink. "</link>\n".
            "\t\t<pubDate>". $v['_d']. "</pubDate>\n".
            "\t\t<description><![CDATA[". $v['_descr']. "]]></description>\n".
            "\t\t<guid isPermaLink=\"false\">$guid</guid>\n".
            "\t</item>\n\n";
    }

    $s .= "</channel>\n</rss>";

    header("Content-type: text/xml; charset=utf-8");
    print $s;
}
