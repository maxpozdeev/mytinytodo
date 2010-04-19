<?php

/*
	This file is part of myTinyTodo.
	(C) Copyright 2009 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/ 

set_error_handler('myErrorHandler');
set_exception_handler('myExceptionHandler');

require_once('./init.php');

$lang = Lang::instance();

if(isset($_GET['loadLists']))
{
	if($needAuth && !is_logged()) $sqlWhere = 'WHERE published=1';
	else $sqlWhere = '';
	$t = array();
	$t['total'] = 0;
	$q = $db->dq("SELECT * FROM {$db->prefix}lists $sqlWhere ORDER BY ow ASC, id ASC");
	while($r = $q->fetch_assoc($q))
	{
		$t['total']++;
		$t['list'][] = prepareList($r);
	}
	echo json_encode($t); 
	exit;
}
elseif(isset($_GET['loadTasks']))
{
	stop_gpc($_GET);
	$listId = (int)_get('list');
	check_read_access($listId);
	$sqlWhere = ' AND list_id='. $listId;
	if(_get('compl') == 0) $sqlWhere .= ' AND compl=0';
	$inner = '';
	$tag = trim(_get('t'));
	if($tag != '') {
		$tag_id = get_tag_id($tag, $listId);
		$inner = "INNER JOIN {$db->prefix}tag2task ON id=task_id";
		$sqlWhere .= " AND tag_id=$tag_id ";
	}
	$s = trim(_get('s'));
	if($s != '') $sqlWhere .= " AND (title LIKE ". $db->quoteForLike("%%%s%%",$s). " OR note LIKE ". $db->quoteForLike("%%%s%%",$s). ")";
	$sort = (int)_get('sort');
	$sqlSort = "ORDER BY compl ASC, ";
	if($sort == 1) $sqlSort .= "prio DESC, ddn ASC, duedate ASC, ow ASC";
	elseif($sort == 2) $sqlSort .= "ddn ASC, duedate ASC, prio DESC, ow ASC";
	else $sqlSort .= "ow ASC";
	$tz = (int)_get('tz');
	if(Config::get('autotz')==0 || $tz<-720 || $tz>720 || $tz%30!=0) $tz = round(date('Z')/60);
	$t = array();
	$t['total'] = 0;
	$t['list'] = array();
	$q = $db->dq("SELECT *, duedate IS NULL AS ddn FROM {$db->prefix}todolist $inner WHERE 1=1 $sqlWhere $sqlSort");
	while($r = $q->fetch_assoc($q))
	{
		$t['total']++;
		$t['list'][] = prepareTaskRow($r, $tz);
	}
	if(_get('setCompl') && have_write_access()) {
		$bitwise = (_get('compl') == 0) ? 'taskview & ~1' : 'taskview | 1';
		$db->dq("UPDATE {$db->prefix}lists SET taskview=$bitwise WHERE id=$listId");
	}
	echo json_encode($t); 
	exit;
}
elseif(isset($_GET['newTask']))
{
	check_write_access();
	stop_gpc($_POST);
	$t = array();
	$t['total'] = 0;
	$listId = (int)_post('list');
	$title = trim(_post('title'));
	$prio = 0;
	$tags = '';
	if(Config::get('smartsyntax') != 0)
	{
		$a = parse_smartsyntax($title);
		if($a === false) {
			echo json_encode($t);
			exit;
		}
		$title = $a['title'];
		$prio = $a['prio'];
		$tags = $a['tags'];
	}
	if($title == '') {
		echo json_encode($t);
		exit;
	}
	if(Config::get('autotag')) $tags .= ','._post('tag');
	$tz = (int)_post('tz');
	if(Config::get('autotz')==0 || $tz<-720 || $tz>720 || $tz%30!=0 ) $tz = round(date('Z')/60);
	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=0");
	$db->ex("BEGIN");
	$db->dq("INSERT INTO {$db->prefix}todolist (list_id,title,d_created,ow,prio) VALUES ($listId,?,?,$ow,$prio)", array($title, time()));
	$id = $db->last_insert_id();
	if($tags)
	{
		$tag_ids = prepare_tags($tags, $listId);
		if($tag_ids) {
			update_task_tags($id, $tag_ids);
			$db->ex("UPDATE {$db->prefix}todolist SET tags=? WHERE id=$id", $tags);
		}
	}
	$db->ex("COMMIT");
	$r = $db->sqa("SELECT * FROM {$db->prefix}todolist WHERE id=$id");
	$t['list'][] = prepareTaskRow($r, $tz);
	$t['total'] = 1;
	echo json_encode($t); 
	exit;
}
elseif(isset($_GET['fullNewTask']))
{
	check_write_access();
	stop_gpc($_POST);
	$listId = (int)_post('list');
	$title = trim(_post('title'));
	$note = str_replace("\r\n", "\n", trim(_post('note')));
	$prio = (int)_post('prio');
	if($prio < -1) $prio = -1;
	elseif($prio > 2) $prio = 2;
	$duedate = parse_duedate(trim(_post('duedate')));
	$t = array();
	$t['total'] = 0;
	if($title == '') {
		echo json_encode($t);
		exit;
	}
	$tags = trim(_post('tags'));
	if(Config::get('autotag')) $tags .= ','._post('tag');
	$tz = (int)_post('tz');
	if( Config::get('autotz')==0 || $tz<-720 || $tz>720 || $tz%30!=0 ) $tz = round(date('Z')/60);
	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=0");
	if(is_null($duedate)) $duedate = 'NULL'; else $duedate = $db->quote($duedate);
	$db->ex("BEGIN");
	$db->dq("INSERT INTO {$db->prefix}todolist (list_id,title,d_created,ow,prio,note,duedate) VALUES($listId,?,?,$ow,$prio,?,$duedate)", array($title,time(),$note));
	$id = $db->last_insert_id();
	if($tags)
	{
		$tag_ids = prepare_tags($tags, $listId);
		if($tag_ids) {
			update_task_tags($id, $tag_ids);
			$db->ex("UPDATE {$db->prefix}todolist SET tags=? WHERE id=$id", $tags);
		}
	}
	$db->ex("COMMIT");
	$r = $db->sqa("SELECT * FROM {$db->prefix}todolist WHERE id=$id");
	$t['list'][] = prepareTaskRow($r, $tz);
	$t['total'] = 1;
	echo json_encode($t); 
	exit;
}
elseif(isset($_GET['deleteTask']))
{
	$id = (int)$_GET['deleteTask'];
	$deleted = deleteTask($id);
	$t = array();
	$t['total'] = $deleted;
	$t['list'][] = array('id'=>$id);
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['completeTask']))
{
	check_write_access();
	$id = (int)$_GET['completeTask'];
	$compl = _get('compl') ? 1 : 0;
	if($compl) 	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE compl=1");
	else $ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE compl=0");
	$dateCompleted = $compl ? time() : 0;
	$db->dq("UPDATE {$db->prefix}todolist SET compl=$compl,ow=$ow,d_completed=? WHERE id=$id", array($dateCompleted));
	$tz = (int)_get('tz');
	if(Config::get('autotz')==0 || $tz<-720 || $tz>720 || $tz%30!=0) $tz = round(date('Z')/60);
	$t = array();
	$t['total'] = 1;
	$t['list'][] = array('id'=>$id, 'compl'=>$compl, 'ow'=>$ow, 'dateCompleted'=>$dateCompleted?htmlarray(timestampToDatetime($dateCompleted, $tz)):'');
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['editNote']))
{
	check_write_access();
	$id = (int)$_GET['editNote'];
	stop_gpc($_POST);
	$note = str_replace("\r\n", "\n", trim(_post('note')));
	$db->dq("UPDATE {$db->prefix}todolist SET note=? WHERE id=$id", $note);
	$t = array();
	$t['total'] = 1;
	$t['list'][] = array('id'=>$id, 'note'=>nl2br(escapeTags($note)), 'noteText'=>(string)$note);
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['editTask']))
{
	check_write_access();
	$id = (int)$_GET['editTask'];
	stop_gpc($_POST);
	$listId = (int)_post('list');
	$title = trim(_post('title'));
	$note = str_replace("\r\n", "\n", trim(_post('note')));
	$prio = (int)_post('prio');
	if($prio < -1) $prio = -1;
	elseif($prio > 2) $prio = 2;
	$duedate = parse_duedate(trim(_post('duedate')));
	$t = array();
	$t['total'] = 0;
	if($title == '') {
		echo json_encode($t);
		exit;
	}
	$tags = trim(_post('tags'));
	$tz = (int)_post('tz');
	if( Config::get('autotz')==0 || $tz<-720 || $tz>720 || $tz%30!=0 ) $tz = round(date('Z')/60);
	$db->ex("BEGIN");
	$tag_ids = prepare_tags($tags, $listId); 
	$cur_ids = get_task_tags($id);
	if($cur_ids) {
		$ids = implode(',', $cur_ids);
		$db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id=$id");
		$db->dq("UPDATE {$db->prefix}tags SET tags_count=tags_count-1 WHERE id IN ($ids)");
	}
	if($tag_ids) {
		update_task_tags($id, $tag_ids);
	}
	if(is_null($duedate)) $duedate = 'NULL'; else $duedate = $db->quote($duedate);
	$db->dq("UPDATE {$db->prefix}todolist SET title=?,note=?,prio=?,tags=?,duedate=$duedate WHERE id=$id", array($title,$note,$prio,$tags));
	$db->ex("COMMIT");
	$r = $db->sqa("SELECT * FROM {$db->prefix}todolist WHERE id=$id");
	if($r) {
		$t['list'][] = prepareTaskRow($r, $tz);
		$t['total'] = 1;
	}
	echo json_encode($t); 
	exit;
}
elseif(isset($_GET['changeOrder']))
{
	check_write_access();
	stop_gpc($_POST);
	$s = _post('order');
	parse_str($s, $order);
	$t = array();
	$t['total'] = 0;
	if($order)
	{
		$ad = array();
		foreach($order as $id=>$diff) {
			$ad[(int)$diff][] = (int)$id;
		}
		$db->ex("BEGIN");
		foreach($ad as $diff=>$ids) {
			if($diff >=0) $set = "ow=ow+".$diff;
			else $set = "ow=ow-".abs($diff);
			$db->dq("UPDATE {$db->prefix}todolist SET $set WHERE id IN (".implode(',',$ids).")");
		}
		$db->ex("COMMIT");
		$t['total'] = 1;
	}
	echo json_encode($t);
	exit;
}
elseif(isset($_POST['login']))
{
	$t = array('logged' => 0);
	if(!$needAuth) {
		$t['disabled'] = 1;
		echo json_encode($t);
		exit;
	}
	stop_gpc($_POST);
	$password = _post('password');
	if($password == Config::get('password')) {
		$t['logged'] = 1;
		session_regenerate_id(1);
		$_SESSION['logged'] = 1;
	}
	echo json_encode($t);
	exit;
}
elseif(isset($_POST['logout']))
{
	unset($_SESSION['logged']);
	$t = array('logged' => 0);
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['suggestTags']))
{
	$listId = (int)_get('list');
	check_read_access($listId);
	$begin = trim(_get('q'));
	$limit = (int)_get('limit');
	if($limit<1) $limit = 8;
	$q = $db->dq("SELECT name,id FROM {$db->prefix}tags WHERE list_id=$listId AND name LIKE ". $db->quoteForLike('%s%%',$begin). " AND tags_count>0 ORDER BY name LIMIT $limit");
	$s = '';
	while($r = $q->fetch_row()) {
		$s .= "$r[0]|$r[1]\n";
	}
	echo htmlarray($s);
	exit; 
}
elseif(isset($_GET['setPrio']))
{
	check_write_access();
	$id = (int)$_GET['setPrio'];
	$prio = (int)_get('prio');
	if($prio < -1) $prio = -1;
	elseif($prio > 2) $prio = 2;
	$db->ex("UPDATE {$db->prefix}todolist SET prio=$prio WHERE id=$id");
	$t = array();
	$t['total'] = 1;
	$t['list'][] = array('id'=>$id, 'prio'=>$prio);
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['tagCloud']))
{
	$listId = (int)_get('list');
	check_read_access($listId);
	$a = array();
	$q = $db->dq("SELECT name,tags_count FROM {$db->prefix}tags WHERE list_id=$listId AND tags_count>0 ORDER BY tags_count ASC");
	while($r = $q->fetch_row()) {
		$a[$r[0]] = $r[1];
	}
	$t = array();
	$t['total'] = 0;
	$count = sizeof($a);
	if(!$count) {
		echo json_encode($t);
		exit;
	}
	$qmax = max(array_values($a));
	$qmin = min(array_values($a));
	if($count >= 10) $grades = 10;
	else $grades = $count;
	$step = ($qmax - $qmin)/$grades;
	foreach($a as $tag=>$q) {
		$t['cloud'][] = array('tag'=>htmlarray($tag), 'w'=> tag_size($qmin,$q,$step) );
	}
	$t['total'] = $count;
	echo json_encode($t);
	exit;
}
elseif(isset($_POST['addList']))
{
	check_write_access();
	stop_gpc($_POST);
	$t = array();
	$t['total'] = 0;
	$name = str_replace(array('"',"'",'<','>','&'),array('','','','',''),trim(_post('name')));
	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}lists");
	$db->dq("INSERT INTO {$db->prefix}lists (name,ow) VALUES (?,?)", array($name,$ow));
	$id = $db->last_insert_id();
	$t['total'] = 1;
	$r = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$id");
	$t['list'][] = prepareList($r);
	echo json_encode($t);
	exit;
}
elseif(isset($_POST['renameList']))
{
	check_write_access();
	stop_gpc($_POST);
	$t = array();
	$t['total'] = 0;
	$id = (int)_post('id');
	$name = str_replace(array('"',"'",'<','>','&'),array('','','','',''),trim(_post('name')));
	$db->dq("UPDATE {$db->prefix}lists SET name=? WHERE id=$id", array($name));
	$t['total'] = $db->affected();
	$r = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$id");
	$t['list'][] = prepareList($r);
	echo json_encode($t);
	exit;
}
elseif(isset($_POST['deleteList']))
{
	check_write_access();
	stop_gpc($_POST);
	$t = array();
	$t['total'] = 0;
	$id = (int)_post('id');
	$db->ex("BEGIN");
	$db->ex("DELETE FROM {$db->prefix}lists WHERE id=$id");
	$t['total'] = $db->affected();
	if($t['total']) {
		$db->ex("DELETE FROM {$db->prefix}tags WHERE list_id=$id");
		# sqlite doesnt support DELETE FROM INNNER JOIN
		$db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id IN (SELECT id FROM {$db->prefix}todolist WHERE list_id=$id)");
		$db->ex("DELETE FROM {$db->prefix}todolist WHERE list_id=$id");
	}
	$db->ex("COMMIT");
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['setSort']))
{
	check_write_access();
	$listId = (int)_post('list');
	$sort = (int)_post('sort');
	if($sort < 0 || $sort > 2) $sort = 0;
	$db->ex("UPDATE {$db->prefix}lists SET sorting=$sort WHERE id=$listId");
	echo json_encode(array('total'=>1));
	exit;
}
elseif(isset($_GET['publishList']))
{
	check_write_access();
	$listId = (int)_post('list');
	$publish = (int)_post('publish');
	$db->ex("UPDATE {$db->prefix}lists SET published=? WHERE id=$listId", array($publish ? 1 : 0));
	echo json_encode(array('total'=>1));
	exit;
}
elseif(isset($_GET['moveTask']))
{
	check_write_access();
	$id = (int)_post('id');
//	$fromId = (int)_post('from');
	$toId = (int)_post('to');
	$r = moveTask($id, $toId);
	echo json_encode(array('total'=>$r?1:0));
	exit;
}
elseif(isset($_GET['changeListOrder']))
{
	check_write_access();
	stop_gpc($_POST);
	$order = (array)_post('list');
	$t = array();
	$t['total'] = 0;
	if($order)
	{
		$a = array();
		$setCase = '';
		foreach($order as $ow=>$id) {
			$id = (int)$id;
			$a[] = $id;
			$setCase .= "WHEN id=$id THEN $ow\n";
		}
		$ids = implode($a, ',');
		$db->dq("UPDATE {$db->prefix}lists SET ow = CASE\n $setCase END WHERE id IN ($ids)");
		$t['total'] = 1;
	}
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['parseTaskStr']))
{
	check_write_access();
	stop_gpc($_POST);
	$t = array(
		'title' => trim(_post('title')),
		'prio' => 0,
		'tags' => ''
	);
	if(Config::get('smartsyntax') != 0 && (false !== $a = parse_smartsyntax($t['title'])))
	{
		$t['title'] = $a['title'];
		$t['prio'] = $a['prio'];
		$t['tags'] = $a['tags'];
	}
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['clearCompletedInList']))
{
	check_write_access();
	stop_gpc($_POST);
	$t = array();
	$t['total'] = 0;
	$listId = (int)_post('list');
	$setCase = '';
	$aId = array();
	$q = $db->dq("SELECT tag_id,COUNT(task_id) FROM {$db->prefix}tag2task INNER JOIN {$db->prefix}todolist ON task_id=id WHERE list_id=$listId AND compl=1 GROUP BY tag_id");
	while($r = $q->fetch_row()) {
		$aId[] = $r[0];
		$setCase .= "WHEN id=$r[0] THEN $r[1]\n";	
	}
	$db->ex("BEGIN");
	if($aId) {
		$ids = implode(',', $aId);
		$db->ex("UPDATE {$db->prefix}tags SET tags_count=tags_count - CASE\n $setCase END WHERE id IN ($ids)");
		$db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id IN (SELECT id FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=1)");
	}
	$db->ex("DELETE FROM {$db->prefix}todolist WHERE list_id=$listId and compl=1");
	$t['total'] = $db->affected();
	$db->ex("DELETE FROM {$db->prefix}tags WHERE tags_count < 1");
	$db->ex("COMMIT");
	echo json_encode($t);
	exit;
}

###################################################################################################

function prepareTaskRow($r, $tz)
{
	global $lang;
	$dueA = prepare_duedate($r['duedate'], $tz);
	$dCreated = timestampToDatetime($r['d_created'], $tz);
	return array(
		'id' => $r['id'],
		'title' => escapeTags($r['title']),
		'date' => htmlarray($dCreated),
		'dateInline' => htmlarray(sprintf($lang->get('taskdate_inline'), $dCreated)),
		'dateCompleted' => $r['d_completed'] ? htmlarray(timestampToDatetime($r['d_completed'], $tz)) : '',
		'compl' => (int)$r['compl'],
		'prio' => $r['prio'],
		'note' => nl2br(escapeTags($r['note'])),
		'noteText' => (string)$r['note'],
		'ow' => (int)$r['ow'],
		'tags' => htmlarray($r['tags']),
		'duedate' => $dueA['formatted'],
		'dueClass' => $dueA['class'],
		'dueStr' => htmlarray($dueA['str']),
		'dueInt' => date2int($r['duedate']),
	);
}

function check_read_access($listId = null)
{
	global $db;
	if(Config::get('password') == '') return true;
	if(is_logged()) return true;
	if($listId)
	{
		$id = $db->sq("SELECT id FROM {$db->prefix}lists WHERE id=? AND published=1", array($listId));
		if($id) return;
	}
	echo json_encode( array('total'=>0, 'list'=>array(), 'denied'=>1) );
	exit;
}

function have_write_access()
{
	if(Config::get('password') == '') return true;
	if(is_logged()) return true;
}

function check_write_access()
{
	if(have_write_access()) return;
	echo json_encode( array('total'=>0, 'list'=>array(), 'denied'=>1) );
	exit;
}

function prepare_tags(&$tags_str, $listId)
{
	$tag_ids = array();
	$tag_names = array();
	$tags = explode(',', $tags_str);
	foreach($tags as $v)
	{ 
		# remove duplicate tags?
		$tag = str_replace(array('"',"'",'<','>','&'),array('','','','',''),trim($v));
		if($tag == '') continue;
		list($tag_id,$tag_name) = get_or_create_tag($tag, $listId);
		if($tag_id && !in_array($tag_id, $tag_ids)) {
			$tag_ids[] = $tag_id;
			$tag_names[] = $tag_name;
		}
	}
	$tags_str = implode(',', $tag_names);
	return $tag_ids;
}

function get_or_create_tag($name, $listId)
{
	global $db;
	$tag = $db->sq("SELECT id,name FROM {$db->prefix}tags WHERE list_id=? AND name=?", array($listId, $name));
	if($tag) return $tag;

	# need to create tag
	$db->ex("INSERT INTO {$db->prefix}tags (name,list_id) VALUES (?,?)", array($name,$listId));
	return array($db->last_insert_id(), $name);
}

function get_tag_id($tag, $listId)
{
	global $db;
	$id = $db->sq("SELECT id FROM {$db->prefix}tags WHERE list_id=? AND name=?", array($listId, $tag));
	return $id ? $id : 0;
}

function get_task_tags($id)
{
	global $db;
	$q = $db->dq("SELECT tag_id FROM {$db->prefix}tag2task WHERE task_id=?", $id);
	$a = array();
	while($r = $q->fetch_row()) {
		$a[] = $r[0];
	}
	return $a;
}

function update_task_tags($id, $tag_ids)
{
	global $db;
	foreach($tag_ids as $v) {
		$db->ex("INSERT INTO {$db->prefix}tag2task (task_id,tag_id) VALUES ($id,$v)");
	}
	$db->ex("UPDATE {$db->prefix}tags SET tags_count=tags_count+1 WHERE id IN (". implode(',', $tag_ids). ")");
}

function parse_smartsyntax($title)
{
	$a = array();
	if(!preg_match("|^(/([+-]{0,1}\d+)?/)?(.*?)(\s+/([^/]*)/$)?$|", $title, $m)) return false;
	$a['prio'] = isset($m[2]) ? (int)$m[2] : 0;
	$a['title'] = isset($m[3]) ? trim($m[3]) : '';
	$a['tags'] = isset($m[5]) ? trim($m[5]) : '';
	if($a['prio'] < -1) $a['prio'] = -1;
	elseif($a['prio'] > 2) $a['prio'] = 2;
	return $a;
}

function tag_size($qmin, $q, $step)
{
	if($step == 0) return 1;
	$v = ceil(($q - $qmin)/$step);
	if($v == 0) return 0;
	else return $v-1;

}

function parse_duedate($s)
{
	$y = $m = $d = 0;
	if(preg_match("|^(\d+)-(\d+)-(\d+)\b|", $s, $ma)) {
		$y = (int)$ma[1]; $m = (int)$ma[2]; $d = (int)$ma[3];
	}
	elseif(preg_match("|^(\d+)\/(\d+)\/(\d+)\b|", $s, $ma))
	{
		if(Config::get('duedateformat') == 4) {
			$d = (int)$ma[1]; $m = (int)$ma[2]; $y = (int)$ma[3];
		} else {
			$m = (int)$ma[1]; $d = (int)$ma[2]; $y = (int)$ma[3];
		}
	}
	elseif(preg_match("|^(\d+)\.(\d+)\.(\d+)\b|", $s, $ma)) {
		$d = (int)$ma[1]; $m = (int)$ma[2]; $y = (int)$ma[3];
	}
	elseif(preg_match("|^(\d+)\.(\d+)\b|", $s, $ma)) {
		$d = (int)$ma[1]; $m = (int)$ma[2]; 
		$a = explode(',', date('Y,m,d'));
		if( $m<(int)$a[1] || ($m==(int)$a[1] && $d<(int)$a[2]) ) $y = (int)$a[0]+1; 
		else $y = (int)$a[0];
	}
	elseif(preg_match("|^(\d+)\/(\d+)\b|", $s, $ma))
	{
		if(Config::get('duedateformat') == 4) {
			$d = (int)$ma[1]; $m = (int)$ma[2];
		} else {
			$m = (int)$ma[1]; $d = (int)$ma[2];
		}
		$a = explode(',', date('Y,m,d'));
		if( $m<(int)$a[1] || ($m==(int)$a[1] && $d<(int)$a[2]) ) $y = (int)$a[0]+1; 
		else $y = (int)$a[0];
	}
	else return null;
	if($y < 100) $y = 2000 + $y;
	elseif($y < 1000 || $y > 2099) $y = 2000 + (int)substr((string)$y, -2);
	if($m > 12) $m = 12;
	$maxdays = daysInMonth($m,$y);
	if($m < 10) $m = '0'.$m;
	if($d > $maxdays) $d = $maxdays;
	elseif($d < 10) $d = '0'.$d;
	return "$y-$m-$d";
}

function prepare_duedate($duedate, $tz)
{
	global $lang;

	$a = array( 'class'=>'', 'str'=>'', 'formatted'=>'' );
	if($duedate == '') {
		return $a;
	}
	$ad = explode('-', $duedate);
	$at = explode('-', gmdate('Y-m-d', time() + $tz*60));
	$diff = mktime(0,0,0,$ad[1],$ad[2],$ad[0]) - mktime(0,0,0,$at[1],$at[2],$at[0]);

	if($diff < -604800 && $ad[0] == $at[0])	{ $a['class'] = 'past'; $a['str'] = formatDate3(Config::get('dateformatshort'), (int)$ad[0], (int)$ad[1], (int)$ad[2], $lang); }
	elseif($diff < -604800)	{ $a['class'] = 'past'; $a['str'] = formatDate3(Config::get('dateformat'), (int)$ad[0], (int)$ad[1], (int)$ad[2], $lang); }
	elseif($diff < -86400)		{ $a['class'] = 'past'; $a['str'] = sprintf($lang->get('daysago'),ceil(abs($diff)/86400)); }
	elseif($diff < 0)			{ $a['class'] = 'past'; $a['str'] = $lang->get('yesterday'); }
	elseif($diff < 86400)		{ $a['class'] = 'today'; $a['str'] = $lang->get('today'); }
	elseif($diff < 172800)		{ $a['class'] = 'today'; $a['str'] = $lang->get('tomorrow'); }
	elseif($diff < 691200)		{ $a['class'] = 'soon'; $a['str'] = sprintf($lang->get('indays'),ceil($diff/86400)); }
	elseif($ad[0] == $at[0])	{ $a['class'] = 'future'; $a['str'] = formatDate3(Config::get('dateformatshort'), (int)$ad[0], (int)$ad[1], (int)$ad[2], $lang); }
	else						{ $a['class'] = 'future'; $a['str'] = formatDate3(Config::get('dateformat'), (int)$ad[0], (int)$ad[1], (int)$ad[2], $lang); }

	if(Config::get('duedateformat') == 2) $a['formatted'] = (int)$ad[1].'/'.(int)$ad[2].'/'.$ad[0];
	elseif(Config::get('duedateformat') == 3) $a['formatted'] = $ad[2].'.'.$ad[1].'.'.$ad[0];
	elseif(Config::get('duedateformat') == 4) $a['formatted'] = $ad[2].'/'.$ad[1].'/'.$ad[0];
	else $a['formatted'] = $duedate;

	return $a;
}

function date2int($d)
{
	if(!$d) return 33330000;
	$ad = explode('-', $d);
	$s = $ad[0];
	if(strlen($ad[1]) < 2) $s .= "0$ad[1]"; else $s .= $ad[1];
	if(strlen($ad[2]) < 2) $s .= "0$ad[2]"; else $s .= $ad[2];
	return (int)$s;
}

function daysInMonth($m, $y=0)
{
	if($y == 0) $y = (int)date('Y');
	$a = array(1=>31,(($y-2000)%4?28:29),31,30,31,30,31,31,30,31,30,31);
	if(isset($a[$m])) return $a[$m]; else return 0;
}

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
	if($errno==E_ERROR || $errno==E_CORE_ERROR || $errno==E_COMPILE_ERROR || $errno==E_USER_ERROR || $errno==E_PARSE) $error = 'Error';
	elseif($errno==E_WARNING || $errno==E_CORE_WARNING || $errno==E_COMPILE_WARNING || $errno==E_USER_WARNING || $errno==E_STRICT) {
		if(error_reporting() & $errno) $error = 'Warning'; else return;
	}
	elseif($errno==E_NOTICE || $errno==E_USER_NOTICE) {
		if(error_reporting() & $errno) $error = 'Notice'; else return;
	}
	elseif(defined('E_DEPRECATED') && ($errno==E_DEPRECATED || $errno==E_USER_DEPRECATED)) { # since 5.3.0
		if(error_reporting() & $errno) $error = 'Notice'; else return;
	}
	else $error = "Error ($errno)";	# here may be E_RECOVERABLE_ERROR
	throw new Exception("$error: '$errstr' in $errfile:$errline", -1);
}

function myExceptionHandler($e)
{
	if(-1 == $e->getCode()) {
		echo $e->getMessage(); exit;
	}
	echo 'Exception: \''. $e->getMessage() .'\' in '. $e->getFile() .':'. $e->getLine();
	exit;
}

function deleteTask($id)
{
	check_write_access();
	global $db;
	$tags = get_task_tags($id);
	$db->ex("BEGIN");
	if($tags) {
		$s = implode(',', $tags);
		$db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id=$id");
		$db->ex("UPDATE {$db->prefix}tags SET tags_count=tags_count-1 WHERE id IN ($s)");
		$db->ex("DELETE FROM {$db->prefix}tags WHERE tags_count < 1");	# slow on large amount of tags
	}
	$db->dq("DELETE FROM {$db->prefix}todolist WHERE id=$id");
	$affected = $db->affected();
	$db->ex("COMMIT");
	return $affected;
}

function moveTask($id, $listId)
{
	check_write_access();
	global $db;
	$r = $db->sqa("SELECT * FROM {$db->prefix}todolist WHERE id=?", array($id));
	if(!$r || $listId == $r['list_id']) return false;
	if(!$db->sq("SELECT COUNT(*) FROM {$db->prefix}lists WHERE id=?", $listId))
		return false;

	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=? AND compl=?", array($listId, $r['compl']?1:0));
	$tags = get_task_tags($id);
	$db->ex("BEGIN");
	if($tags)
	{
		$s = implode(',', $tags);
		$db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id=?", $id);
		$db->ex("UPDATE {$db->prefix}tags SET tags_count=tags_count-1 WHERE id IN ($s)");
		$db->ex("DELETE FROM {$db->prefix}tags WHERE tags_count < 1");	#slow
		update_task_tags($id, prepare_tags($r['tags'], $listId));
	}
	$db->dq("UPDATE {$db->prefix}todolist SET list_id=?, ow=? WHERE id=?", array($listId, $ow, $id));
	$db->ex("COMMIT");
	return true;
}

function prepareList($row)
{
	$taskview = (int)$row['taskview'];
	return array(
		'id' => $row['id'],
		'name' => htmlarray($row['name']),
		'sort' => (int)$row['sorting'],
		'published' => $row['published'] ? 1 :0,
		'showCompl' => $taskview & 1 ? 1 : 0,
//		'showNotes' => $taskview & 2 ? 1 : 0,
	);
}

?>