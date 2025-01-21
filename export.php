<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2010-2011,2019-2021 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

//$dontStartSession = 1;
require_once('./init.php');

$listId = (int)_get('list');
$db = DBConnection::instance();
$listData = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$listId");
if ( $listData && !is_logged() && !$listData['published'] ) {
    $extra = json_decode($listData['extra'] ?? '', true, 10, JSON_INVALID_UTF8_SUBSTITUTE);
    $feedKey = (string) ($extra['feedKey'] ?? '');
    $inFeedKey = trim(_get('key'));
    if ($feedKey == '' || $feedKey != $inFeedKey) {
        die("Access denied.");
    }
}
if (!$listData) {
    die("No list found.");
}

$data = DBCore::default()->getTasksByListId($listId, '', (int)$listData['sorting']);

if (_get('format') == 'ical') {
    printICal($listData, $data);
}
else {
    printCSV($listData, $data);
}


function printCSV(array $listData, array $data)
{
    $s = "\xEF\xBB\xBF". "Completed;Priority;Task;Notes;Tags;Due;DateCreated;DateCompleted\n";
    foreach($data as $r)
    {
        $s .= ($r['compl']?'1':'0'). ';'.
            $r['prio']. ';'. escape_csv($r['title'] ?? ''). ';'.
            escape_csv($r['note'] ?? ''). ';'.
            escape_csv($r['tags'] ?? ''). ';'.
            $r['duedate']. ';'.
            date('Y-m-d H:i:s O',$r['d_created']). ';'.
            ($r['d_completed'] ? date('Y-m-d H:i:s O',$r['d_completed']) :''). "\n";
    }
    header('Content-type: text/csv; charset=utf-8');
    header('Content-disposition: attachment; filename=list_'.(int)$listData['id'].'.csv');
    print $s;
}

function escape_csv(string $v)
{
    //escape formulas
    $nf = '';
    $trimmed = ltrim($v);
    if (strlen($trimmed) > 0 && in_array(substr($trimmed, 0, 1), array('=', '+', '-', '@'))) {
        $nf = "'";
    }
    return '"'. $nf. str_replace('"', '""', $v). '"';
}

function printICal(array $listData, array $data)
{
    $mttToIcalPrio = array("1" => 5, "2" => 1);
    $s = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nMETHOD:PUBLISH\r\nCALSCALE:GREGORIAN\r\nPRODID:-//myTinyTodo//iCalendar Export v1.4//EN\r\n".
        "X-WR-CALNAME:". $listData['name']. "\r\nX-MTT-TIMEZONE:".Config::get('timezone')."\r\n";
    # to-do
    foreach($data as $r)
    {
        $a = array();
        $a[] = "BEGIN:VTODO";
        $a[] = "UID:". $r['uuid'];
        $a[] = "CREATED:". gmdate('Ymd\THis\Z', $r['d_created']);
        $a[] = "DTSTAMP:". gmdate('Ymd\THis\Z', $r['d_edited']);
        $a[] = "LAST-MODIFIED:". gmdate('Ymd\THis\Z', $r['d_edited']);
        $a[] = utf8chunks("SUMMARY:". $r['title']);
        if($r['duedate']) {
            $dda = explode('-', $r['duedate']);
            $a[] = "DUE;VALUE=DATE:".sprintf("%u%02u%02u", $dda[0], $dda[1], $dda[2]);
        }
        # Apple's iCal priorities: low-9, medium-5, high-1
        if($r['prio'] > 0 && isset($mttToIcalPrio[$r['prio']])) $a[] = "PRIORITY:". $mttToIcalPrio[$r['prio']];
        $a[] = "X-MTT-PRIORITY:". $r['prio'];

        $descr = array();
        if($r['tags'] != '') $descr[] = Lang::instance()->get('tags'). ": ". str_replace(',', ', ', $r['tags']);
        if($r['note'] != '') $descr[] = Lang::instance()->get('note'). ": ". $r['note'];
        if($descr) $a[] = utf8chunks("DESCRIPTION:". str_replace("\n", '\\n', implode("\n",$descr)));

        if($r['compl']) {
            $a[] = "STATUS:COMPLETED"; #used in Sunbird
            $a[] = "COMPLETED:". gmdate('Ymd\THis\Z', $r['d_completed']);
            #$a[] = "PERCENT-COMPLETE:100"; #used in Sunbird
        }
        if($r['tags'] != '') $a[] = utf8chunks("X-MTT-TAGS:". $r['tags']);
        $a[] = "END:VTODO\r\n";
        $s .= implode("\r\n", $a);
    }
    # events
    foreach($data as $r)
    {
        if(!$r['duedate'] || $r['compl']) continue;  # skip tasks completed and without duedate
        $a = array();
        $a[] = "BEGIN:VEVENT";
        $a[] = "UID:_". $r['uuid'];  # do not duplicate VTODO UID
        $a[] = "CREATED:". gmdate('Ymd\THis\Z', $r['d_created']);
        $a[] = "DTSTAMP:". gmdate('Ymd\THis\Z', $r['d_edited']);
        $a[] = "LAST-MODIFIED:". gmdate('Ymd\THis\Z', $r['d_edited']);
        $a[] = utf8chunks("SUMMARY:". $r['title']);
        if($r['prio'] > 0 && isset($mttToIcalPrio[$r['prio']])) $a[] = "PRIORITY:". $mttToIcalPrio[$r['prio']];
        $dda = explode('-', $r['duedate']);
        $a[] = "DTSTART;VALUE=DATE:".sprintf("%u%02u%02u", $dda[0], $dda[1], $dda[2]);
        $a[] = "DTEND;VALUE=DATE:".date('Ymd', mktime(1,1,1,$dda[1],$dda[2],$dda[0]) + 86400);
        $descr = array();
        if($r['tags'] != '') $descr[] = Lang::instance()->get('tags'). ": ". str_replace(',', ', ', $r['tags']);
        if($r['note'] != '') $descr[] = Lang::instance()->get('note'). ": ". $r['note'];
        if($descr) $a[] = utf8chunks("DESCRIPTION:". str_replace("\n", '\\n', implode("\n",$descr)));
        $a[] = "END:VEVENT\r\n";
        $s .= implode("\r\n", $a);
    }
    $s .= "END:VCALENDAR\r\n";
    header('Content-type: text/calendar; charset=utf-8');
    header('Content-disposition: attachment; filename=list_'.(int)$listData['id'].'.ics');
    print $s;
}

function utf8chunks($text, $chunklen=75, $delimiter="\r\n\t")
{
    if($text == '') return '';
    preg_match_all('/./u', $text, $m);
    $chars = $m[0];
    $a = array();
    $s = '';
    $max = count($chars);
    for($i=0; $i<$max; $i++)
    {
        $ch = $chars[$i];
        if(strlen($s) + strlen($ch) > $chunklen) { # line should be not more than $chunklen bytes
            $a[] = $s;
            $s = $ch;
        }
        else $s .= $ch;
    }
    if($s != '') $a[] = $s;
    return implode($delimiter, $a);
}
