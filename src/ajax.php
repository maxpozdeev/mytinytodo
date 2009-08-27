<?php

set_error_handler('myErrorHandler');
set_exception_handler('myExceptionHandler');

require_once('./init.php');
require_once('./lang/class.default.php');
require_once('./lang/'.$config['lang'].'.php');
$lang = new Lang();

if(isset($_GET['loadTasks']))
{
	check_read_access();
	stop_gpc($_GET);
	if(_get('compl')) $sqlWhere = '';
	else $sqlWhere = ' AND compl=0';
	$inner = '';
	$tag = trim(_get('t'));
	if($tag != '') {
		$tag_id = get_tag_id($tag);
		$inner = "INNER JOIN tag2task ON id=tag2task.task_id";
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
	if((isset($config['autotz']) && $config['autotz']==0) || $tz<-720 || $tz>720 || $tz%30!=0) $tz = null;
	$t = array();
	$t['total'] = 0;
	$t['list'] = array();
	$q = $db->dq("SELECT *, duedate IS NULL AS ddn FROM todolist $inner WHERE 1=1 $sqlWhere $sqlSort");
	while($r = $q->fetch_assoc($q))
	{
		$t['total']++;
		$t['list'][] = prepareTaskRow($r, $tz);
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
	$title = trim(_post('title'));
	$prio = 0;
	$tags = '';
	if(!isset($config['smartsyntax']) || $config['smartsyntax'] != 0)
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
	if(isset($config['autotag']) && $config['autotag']) $tags .= ','._post('tag');
	$tz = (int)_post('tz');
	if( (isset($config['autotz']) && $config['autotz']==0) || $tz<-720 || $tz>720 || $tz%30!=0 ) $d = strftime("%Y-%m-%d %H:%M:%S");
	else $d = gmdate("Y-m-d H:i:s", time()+$tz*60);
	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM todolist WHERE compl=0");
	$db->ex("BEGIN");
	$db->dq("INSERT INTO todolist (title,d,ow,prio) VALUES (?,?,$ow,$prio)", array($title, $d));
	$id = $db->last_insert_id();
	if($tags)
	{
		$tag_ids = prepare_tags($tags);
		if($tag_ids) {
			update_task_tags($id, $tag_ids);
			$db->ex("UPDATE todolist SET tags=? WHERE id=$id", $tags);
		}
	}
	$db->ex("COMMIT");
	$r = $db->sqa("SELECT * FROM todolist WHERE id=$id");
	$t['list'][] = prepareTaskRow($r);
	$t['total'] = 1;
	echo json_encode($t); 
	exit;
}
elseif(isset($_GET['deleteTask']))
{
	check_write_access();
	$id = (int)$_GET['deleteTask'];
	$tags = get_task_tags($id);
	$db->ex("BEGIN");
	if($tags) {
		$s = implode(',', $tags);
		$db->ex("DELETE FROM tag2task WHERE task_id=$id");
		$db->ex("UPDATE tags SET tags_count=tags_count-1 WHERE id IN ($s)");
		$db->ex("DELETE FROM tags WHERE tags_count < 1");	# slow on large amount of tags
	}
	$db->dq("DELETE FROM todolist WHERE id=$id");
	$affected = $db->affected();
	$db->ex("COMMIT");
	$t = array();
	$t['total'] = $affected;
	$t['list'][] = array('id'=>$id);
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['completeTask']))
{
	check_write_access();
	$id = (int)$_GET['completeTask'];
	$compl = _get('compl') ? 1 : 0;
	if($compl) 	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM todolist WHERE compl=1");
	else $ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM todolist WHERE compl=0");
	$db->dq("UPDATE todolist SET compl=$compl,ow=$ow WHERE id=$id");
	$t = array();
	$t['total'] = 1;
	$t['list'][] = array('id'=>$id, 'compl'=>$compl, 'ow'=>$ow);
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['editNote']))
{
	check_write_access();
	$id = (int)$_GET['editNote'];
	stop_gpc($_POST);
	$note = str_replace("\r\n", "\n", trim(_post('note')));
	$db->dq("UPDATE todolist SET note=? WHERE id=$id", $note);
	$t = array();
	$t['total'] = 1;
	$t['list'][] = array('id'=>$id, 'note'=>nl2br(htmlarray($note)), 'noteText'=>(string)$note);
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['getTask']))
{
	check_read_access();
	$id = (int)$_GET['getTask'];
	$t = array();
	$t['total'] = 0;
	$r = $db->sqa("SELECT * FROM todolist WHERE id=$id");
	if($r) {
		$t['list'][] = prepareTaskRow($r);
		$t['total'] = 1;
	}
	echo json_encode($t); 
	exit;
}
elseif(isset($_GET['editTask']))
{
	check_write_access();
	$id = (int)$_GET['editTask'];
	stop_gpc($_POST);
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
	$db->ex("BEGIN");
	$tag_ids = prepare_tags($tags); 
	$cur_ids = get_task_tags($id);
	if($cur_ids) {
		$ids = implode(',', $cur_ids);
		$db->ex("DELETE FROM tag2task WHERE task_id=$id");
		$db->dq("UPDATE tags SET tags_count=tags_count-1 WHERE id IN ($ids)");
	}
	if($tag_ids) {
		update_task_tags($id, $tag_ids);
	}
	if(is_null($duedate)) $duedate = 'NULL'; else $duedate = $db->quote($duedate);
	$db->dq("UPDATE todolist SET title=?,note=?,prio=?,tags=?,duedate=$duedate WHERE id=$id", array($title,$note,$prio,$tags));
	$db->ex("COMMIT");
	$r = $db->sqa("SELECT * FROM todolist WHERE id=$id");
	if($r) {
		$t['list'][] = prepareTaskRow($r);
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
			$db->dq("UPDATE todolist SET $set WHERE id IN (".implode(',',$ids).")");
		}
		$db->ex("COMMIT");
		$t['total'] = 1;
	}
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['login']))
{
	$t = array('logged' => 0);
	if(!$needAuth) {
		$t['disabled'] = 1;
		echo json_encode($t);
		exit;
	}
	stop_gpc($_POST);
	$password = _post('password');
	if($password == $config['password']) {
		$t['logged'] = 1;
		session_regenerate_id(1);
		$_SESSION['logged'] = 1;
	}
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['logout']))
{
	$_SESSION = array();
	$t = array('logged' => 0);
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['suggestTags']))
{
	check_read_access();
	$begin = trim(_get('q'));
	$limit = (int)_get('limit');
	if($limit<1) $limit = 8;
	$q = $db->dq("SELECT name,id FROM tags WHERE name LIKE ". $db->quoteForLike('%s%%',$begin). " AND tags_count>0 ORDER BY name LIMIT $limit");
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
	$db->ex("UPDATE todolist SET prio=$prio WHERE id=$id");
	$t = array();
	$t['total'] = 1;
	$t['list'][] = array('id'=>$id, 'prio'=>$prio);
	echo json_encode($t);
	exit;
}
elseif(isset($_GET['tagCloud']))
{
	$a = array();
	$q = $db->dq("SELECT name,tags_count FROM tags WHERE tags_count>0 ORDER BY tags_count ASC");
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


function prepareTaskRow($r, $tz=null)
{
	$dueA = prepare_duedate($r['duedate'], $tz);
	return array(
		'id' => $r['id'],
		'title' => htmlarray($r['title']),
		'date' => htmlarray($r['d']),
		'compl' => (int)$r['compl'],
		'prio' => $r['prio'],
		'note' => nl2br(htmlarray($r['note'])),
		'noteText' => (string)$r['note'],
		'ow' => (int)$r['ow'],
		'tags' => htmlarray($r['tags']),
		'duedate' => $dueA['formatted'],
		'dueClass' => $dueA['class'],
		'dueStr' => htmlarray($dueA['str']),
		'dueInt' => date2int($r['duedate']),
	);
}

function check_read_access()
{
	if(canAllRead() || is_logged()) return;
	echo json_encode( array('total'=>0, 'list'=>array(), 'denied'=>1) );
	exit;
}

function check_write_access()
{
	global $config;
	if(!isset($config['password']) || $config['password'] == '') return;
	if(is_logged()) return;
	echo json_encode( array('total'=>0, 'list'=>array(), 'denied'=>1) );
	exit;
}

function prepare_tags(&$tags_str)
{
	$tag_ids = array();
	$tag_names = array();
	$tags = explode(',', $tags_str);
	foreach($tags as $v)
	{ 
		# remove duplicate tags?
		$tag = str_replace(array('"',"'",'<','>','&'),array('','','','',''),trim($v));
		if($tag == '') continue;
		list($tag_id,$tag_name) = get_or_create_tag($tag);
		if($tag_id && !in_array($tag_id, $tag_ids)) {
			$tag_ids[] = $tag_id;
			$tag_names[] = $tag_name;
		}
	}
	$tags_str = implode(',', $tag_names);
	return $tag_ids;
}

function get_or_create_tag($name)
{
	global $db;
	$tag = $db->sq("SELECT id,name FROM tags WHERE name=?", $name);
	if($tag) return $tag;

	# need to create tag
	$db->ex("INSERT INTO tags (name) VALUES (?)", $name);
	return array($db->last_insert_id(), $name);
}

function get_tag_id($tag)
{
	global $db;
	$id = $db->sq("SELECT id FROM tags WHERE name=?", $tag);
	return $id ? $id : 0;
}

function get_task_tags($id)
{
	global $db;
	$q = $db->dq("SELECT tag_id FROM tag2task WHERE task_id=?", $id);
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
		$db->ex("INSERT INTO tag2task (task_id,tag_id) VALUES ($id,$v)");
	}
	$db->ex("UPDATE tags SET tags_count=tags_count+1 WHERE id IN (". implode(',', $tag_ids). ")");
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
	elseif(preg_match("|^(\d+)\/(\d+)\/(\d+)\b|", $s, $ma)) {
		$m = (int)$ma[1]; $d = (int)$ma[2]; $y = (int)$ma[3];
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
	elseif(preg_match("|^(\d+)\/(\d+)\b|", $s, $ma)) {
		$m = (int)$ma[1]; $d = (int)$ma[2];
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

function prepare_duedate($duedate, $tz=null)
{
	global $lang, $config;

	$a = array( 'class'=>'', 'str'=>'', 'formatted'=>'' );
	if($duedate == '') {
		return $a;
	}
	if(is_null($tz)) {
		$ad = explode('-', $duedate);
		$at = explode('-', date('Y-m-d'));
	}
	else {
		$ad = explode('-', $duedate);
		$at = explode('-', gmdate('Y-m-d',time() + $tz*60));
	}
	$diff = mktime(0,0,0,$ad[1],$ad[2],$ad[0]) - mktime(0,0,0,$at[1],$at[2],$at[0]);

	if($diff < -604800 && $ad[0] == $at[0])	{ $a['class'] = 'past'; $a['str'] = $lang->formatMD((int)$ad[1], (int)$ad[2]); }
	elseif($diff < -604800)	{ $a['class'] = 'past'; $a['str'] = $lang->formatYMD((int)$ad[0], (int)$ad[1], (int)$ad[2]); }
	elseif($diff < -86400)		{ $a['class'] = 'past'; $a['str'] = sprintf($lang->get('daysago'),ceil(abs($diff)/86400)); }
	elseif($diff < 0)			{ $a['class'] = 'past'; $a['str'] = $lang->get('yesterday'); }
	elseif($diff < 86400)		{ $a['class'] = 'today'; $a['str'] = $lang->get('today'); }
	elseif($diff < 172800)		{ $a['class'] = 'today'; $a['str'] = $lang->get('tomorrow'); }
	elseif($diff < 691200)		{ $a['class'] = 'soon'; $a['str'] = sprintf($lang->get('indays'),ceil($diff/86400)); }
	elseif($ad[0] == $at[0])	{ $a['class'] = 'future'; $a['str'] = $lang->formatMD((int)$ad[1], (int)$ad[2]); }
	else						{ $a['class'] = 'future'; $a['str'] = $lang->formatYMD((int)$ad[0], (int)$ad[1], (int)$ad[2]); }

	if($config['duedateformat'] == 2) $a['formatted'] = (int)$ad[1].'/'.(int)$ad[2].'/'.$ad[0];
	elseif($config['duedateformat'] == 3) $a['formatted'] = $ad[2].'.'.$ad[1].'.'.$ad[0];
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

?>