<?php

/*
	This file is part of myTinyTodo.
	(C) Copyright 2010 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/

//$dontStartSession = 1;
require_once('./init.php');

$onlyPublishedList = false;
if(!have_write_access()) $onlyPublishedList = true;

$listId = (int)_get('list');
$listData = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$listId ". ($onlyPublishedList ? "AND published=1" : "") );
if(!$listData) {
	die("No such list or access denied");
}

$sqlSort = "ORDER BY compl ASC, ";
if($listData['sorting'] == 1) $sqlSort .= "prio DESC, ddn ASC, duedate ASC, ow ASC";
elseif($listData['sorting'] == 2) $sqlSort .= "ddn ASC, duedate ASC, prio DESC, ow ASC";
else $sqlSort .= "ow ASC";

$data = array();
$q = $db->dq("SELECT *, duedate IS NULL AS ddn FROM {$db->prefix}todolist WHERE list_id=$listId $sqlSort");
while($r = $q->fetch_assoc($q)) 
{
	$data[] = $r; 
}

//$format = _get('format');

 printCSV($listData, $data);


function have_write_access()
{
	if(Config::get('password') == '') return true;
	if(is_logged()) return true;
	return false;
}


function printCSV($listData, $data)
{
	$s = /*chr(0xEF).chr(0xBB).chr(0xBF).*/ "Completed,Priority,Task,Notes,Tags,Due,DateCreated,DateCompleted\n";
	foreach($data as $r)
	{
		$s .= ($r['compl']?'1':'0'). ','. $r['prio']. ','. escape_csv($r['title']). ','.
			escape_csv($r['note']). ','. escape_csv($r['tags']). ','. $r['duedate'].
			','. date('Y-m-d H:i:s O',$r['d_created']). ','. ($r['d_completed'] ? date('Y-m-d H:i:s O',$r['d_completed']) :''). "\n";
	}
	header('Content-type: text/csv');
	header('Content-disposition: attachment; filename=list_'.$listData['id'].'.csv');
	print $s;
}

function escape_csv($v)
{
	return '"'.str_replace('"', '""', $v).'"';
}

?>