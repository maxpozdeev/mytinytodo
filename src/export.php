<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2010-2011,2019-2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

require_once('./init.php');

$listId = (int)_get('list');

$listRepo = new ListRepo(DBConnection::instance());
$list = $listRepo->findRealListById($listId);

if (!$list) {
    if (is_logged())
        die("No list found.");
    else
        die("Access denied.");
}

if (!canReadList($list, trim(_get('key')))) {
    die("Access denied.");
}

$taskRepo = new TaskRepo(DBConnection::instance());
$tasks = $taskRepo->findTasks([$list->id], null, [], '', $taskRepo::SORT_DATE_CREATED);

switch (_get('format')) {
    case 'ical': printICal($list, $tasks); break;
    case 'csv' : printCSV($list, $tasks);  break;
    default: die("Unsupported format requested.");
}


/**
 * @param TaskList $list
 * @param Task[] $tasks
 * @return void
 */
function printCSV(TaskList $list, array $tasks)
{
    $s = "\xEF\xBB\xBF". "Id;Completed;Priority;Task;Notes;Tags;Due;DateCreated;DateCompleted\n";
    foreach($tasks as $task)
    {
        $row = [];
        $row[] = $task->id;
        $row[] = $task->isCompleted ? '1' : '0';
        $row[] = $task->priority;
        $row[] = escape_csv($task->title);
        $row[] = escape_csv($task->note ?? '');
        $row[] = escape_csv( implode(',', $task->tagNames) );
        $row[] = $task->duedate ?? '';
        $row[] = date('Y-m-d H:i:s O', $task->d_created);
        $row[] = $task->d_completed ? date('Y-m-d H:i:s O', $task->d_completed) : '';
        $s .= implode(';', $row). "\n";
    }
    header('Content-type: text/csv; charset=utf-8');
    header('Content-disposition: attachment; filename=list_'. (int)$list->id. '.csv');
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

/**
 * @param TaskList $list
 * @param Task[] $tasks
 * @return void
 */
function printICal(TaskList $list, array $tasks)
{
    $mttToIcalPrio = array(1 => 5, "1" => 5, "2" => 1, 2 => 1, "-1" => 9, -1 => 9);
    $s = "BEGIN:VCALENDAR\r\n".
         "VERSION:2.0\r\n".
         "METHOD:PUBLISH\r\n". #?
         "CALSCALE:GREGORIAN\r\n". #?
         "PRODID:-//myTinyTodo//iCalendar Export v2.0//EN\r\n".
         utf8chunks("NAME:". $list->name). "\r\n".
         utf8chunks("X-WR-CALNAME:". $list->name). "\r\n".
         "X-MTT-TIMEZONE:". Config::get('timezone')."\r\n";

    # to-do
    foreach ($tasks as $task)
    {
        $a = array();
        $a[] = "BEGIN:VTODO";
        $a[] = "UID:". $task->uuid;
        $a[] = "CREATED:". gmdate('Ymd\THis\Z', $task->d_created);
        $a[] = "DTSTAMP:". gmdate('Ymd\THis\Z', $task->d_edited);
        $a[] = "LAST-MODIFIED:". gmdate('Ymd\THis\Z', $task->d_edited);
        $a[] = utf8chunks("SUMMARY:". $task->title);
        if ($task->duedate) {
            $dda = explode('-', $task->duedate);
            $a[] = "DUE;VALUE=DATE:".sprintf("%u%02u%02u", $dda[0], $dda[1], $dda[2]);
        }
        # Apple's iCal and Thunderbird priorities: low-9, medium-5, high-1
        if ($task->priority != 0 && isset($mttToIcalPrio[$task->priority]))
            $a[] = "PRIORITY:". $mttToIcalPrio[$task->priority];
        $a[] = "X-MTT-PRIORITY:". $task->priority;

        $descr = array();
        if ($task->tagNames)
            $descr[] = Lang::instance()->get('tags'). ": ". implode(', ', $task->tagNames);
        if ($task->note && $task->note != '')
            $descr[] = Lang::instance()->get('note'). ": ". $task->note;
        if ($descr)
            $a[] = utf8chunks("DESCRIPTION:". str_replace("\n", '\\n', implode("\n", $descr)));

        if ($task->isCompleted) {
            $a[] = "STATUS:COMPLETED"; #used in Mozilla Thunderbird
            $a[] = "COMPLETED:". gmdate('Ymd\THis\Z', $task->d_completed);
            #$a[] = "PERCENT-COMPLETE:100"; #used in Mozilla Thunderbird
        }
        #if ($task->tagNames)
        #    $a[] = utf8chunks("X-MTT-TAGS:". implode(',', $task->tagNames));

        $a[] = "END:VTODO\r\n";
        $s .= implode("\r\n", $a);
    }

    # events
    foreach ($tasks as $task)
    {
        if (!$task->duedate || $task->isCompleted)
            continue;  # skip tasks completed and without duedate
        $a = array();
        $a[] = "BEGIN:VEVENT";
        $a[] = "UID:_". $task->uuid;  # do not duplicate VTODO UID
        $a[] = "CREATED:". gmdate('Ymd\THis\Z', $task->d_created);
        $a[] = "DTSTAMP:". gmdate('Ymd\THis\Z', $task->d_edited);
        $a[] = "LAST-MODIFIED:". gmdate('Ymd\THis\Z', $task->d_edited);
        $a[] = utf8chunks("SUMMARY:". $task->title);
        if ($task->priority != 0 && isset($mttToIcalPrio[$task->priority]))
            $a[] = "PRIORITY:". $mttToIcalPrio[$task->priority];
        $dda = explode('-', $task->duedate);
        $a[] = "DTSTART;VALUE=DATE:".sprintf("%u%02u%02u", $dda[0], $dda[1], $dda[2]);
        $a[] = "DTEND;VALUE=DATE:".date('Ymd', mktime(1,1,1,(int)$dda[1],(int)$dda[2],(int)$dda[0]) + 86400);

        $descr = array();
        if (count($task->tagNames))
            $descr[] = Lang::instance()->get('tags'). ": ". implode(', ', $task->tagNames);
        if ($task->note && $task->note != '')
            $descr[] = Lang::instance()->get('note'). ": ". $task->note;
        if ($descr)
            $a[] = utf8chunks("DESCRIPTION:". str_replace("\n", '\\n', implode("\n", $descr)));

        $a[] = "END:VEVENT\r\n";
        $s .= implode("\r\n", $a);
    }

    $s .= "END:VCALENDAR\r\n";
    header('Content-type: text/calendar; charset=utf-8');
    header('Content-disposition: attachment; filename=list_'. (int)$list->id. '.ics');
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
