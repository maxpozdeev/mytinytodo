<?php

/*
	This file is part of myTinyTodo.
	(C) Copyright 2009 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/

require_once('./init.php');
if($needAuth && !is_logged())
{
	die("Access denied!<br> Disable password protection or Log in.");
}
$dbclass = strtolower(get_class($db));
$dbtype = ($dbclass == 'database_mysql') ? 'mysql' : 'sqlite';

$lastVer = '1.2';
echo '<html><head><meta name="robots" content="noindex,nofollow"></head><body>'; 
echo "<b>myTinyTodo v$lastVer Setup</b><br><br>";

# exclude sqlite2
if($dbclass == 'database_sqlite') {
	exitMessage("Install/update is not available for sqlite2. Use sqlite v3.");
}

# determine current installed version
$ver = get_ver($db, $dbtype);

if(!$ver)
{
	# install database
	if(!isset($_POST['install'])) {
		exitMessage("This will create myTinyTodo database <form method=post><input type=hidden name=install value=1><input type=submit value=' Install '></form>");
	}

	if($dbtype == 'mysql') 
	{
		try
		{
			$db->ex(
"CREATE TABLE todolist (
 `id` INT UNSIGNED NOT NULL auto_increment,
 `d` DATETIME NOT NULL,
 `compl` TINYINT UNSIGNED NOT NULL default 0,
 `title` VARCHAR(250) NOT NULL,
 `note` TEXT,
 `prio` TINYINT NOT NULL default 0,			/* priority -,0,+ */
 `ow` INT NOT NULL default 0,				/* order weight */
 `tags` VARCHAR(250) NOT NULL default '',	/* denormalization - for fast access to task tags */
 `duedate` DATE default NULL,
  PRIMARY KEY(`id`)
) CHARSET=utf8 ");

			$db->ex(
"CREATE TABLE tags (
 `id` INT UNSIGNED NOT NULL auto_increment,
 `name` VARCHAR(50) NOT NULL,
 `tags_count` INT default 0,
 PRIMARY KEY(`id`),
 UNIQUE KEY(`name`)
) CHARSET=utf8 ");

			$db->ex(
"CREATE TABLE tag2task (
 `tag_id` INT UNSIGNED NOT NULL,
 `task_id` INT UNSIGNED NOT NULL,
 KEY(`tag_id`),
 KEY(`task_id`)
) CHARSET=utf8 ");

		} catch (Exception $e) {
			exitMessage("<b>Error:</b> ". htmlarray($e->getMessage()));
		}
	}
	else 
	{
		try
		{
			$db->ex(
"CREATE TABLE todolist (
 id INTEGER PRIMARY KEY,
 d DATETIME NOT NULL default '0000-00-00',
 compl TINYINT UNSIGNED NOT NULL default 0,
 title VARCHAR(250) NOT NULL,
 note TEXT,
 prio TINYINT NOT NULL default 0,
 ow INT NOT NULL default 0,
 tags VARCHAR(250) NOT NULL default '',
 duedate DATE default NULL
) ");

			$db->ex(
"CREATE TABLE tags (
 id INTEGER PRIMARY KEY,
 name VARCHAR(50) NOT NULL COLLATE NOCASE UNIQUE,
 tags_count INT default 0
) ");

			$db->ex(
"CREATE TABLE tag2task (
 tag_id INTEGER NOT NULL,
 task_id INTEGER NOT NULL
) ");

			$db->ex("CREATE INDEX tag_id ON tag2task (tag_id)");
			$db->ex("CREATE INDEX task_id ON tag2task (task_id)");

		} catch (Exception $e) {
			exitMessage("<b>Error:</b> ". htmlarray($e->getMessage()));
		} 
	}
}
elseif($ver == $lastVer)
{
	exitMessage("Installed version (v$ver) does not require database update.");
}
else
{
	if(!in_array($ver, array('1.1'))) {
		exitMessage("Can not update database. Unsupported version ($ver).");
	}
	if(!isset($_POST['update'])) {
		exitMessage("Update database from v$ver to v1.2 <form method=post><input type=hidden name=update value=1><input type=submit value=' Update '></form>");
	}

	# update process
	if($ver == '1.1')
	{
		update_11_12($db, $dbtype);
	}
}
echo "Done<br><br> <b>Attention!</b> Delete this file for security reasons.";
printFooter();


function get_ver($db, $dbtype)
{
	if(!$db->table_exists('todolist')) return '';
	$v = '1.0';
	if(!$db->table_exists('tags')) return $v;
	$v = '1.1';
	if($dbtype == 'mysql') {
		if(!has_field_mysql($db, 'todolist', 'duedate')) return $v;
	} else {
		if(!has_field_sqlite($db, 'todolist', 'duedate')) return $v;
	}
	$v = '1.2';
	return $v;
}

function exitMessage($s)
{
	echo $s;
	printFooter();
	exit;
}

function printFooter()
{
	echo "</body></html>"; 
}


function has_field_sqlite($db, $table, $field)
{
	$q = $db->dq("PRAGMA table_info(". $db->quote($table). ")");
	while($r = $q->fetch_row()) {
		if($r[1] == $field) return true;
	}
	return false;
}

function has_field_mysql($db, $table, $field)
{
	$q = $db->dq("DESCRIBE `$table`");
	while($r = $q->fetch_row()) {
		if($r[0] == $field) return true;
	}
	return false;
}

### 1.1-1.2 ##########
function update_11_12($db, $dbtype)
{
	echo "Changing database structure...<br>";
	if($dbtype == 'mysql') $db->ex("ALTER TABLE todolist ADD `duedate` DATE default NULL");
	else $db->ex("ALTER TABLE todolist ADD duedate DATE default NULL");

	echo "Fixing broken tags...<br>";
	$db->ex("BEGIN");
	$db->ex("DELETE FROM tags");
	$db->ex("DELETE FROM tag2task");
	$q = $db->dq("SELECT id,tags FROM todolist");
	while($r = $q->fetch_assoc())
	{
		if($r['tags'] == '') continue;
		$tag_ids = prepare_tags($r['tags']); 
		if($tag_ids) update_task_tags($r['id'], $tag_ids);
	}
	$db->ex("COMMIT");
}

function prepare_tags(&$tags_str)
{
	$tag_ids = array();
	$tag_names = array();
	$tags = explode(',', $tags_str);
	foreach($tags as $v)
	{ 
		# remove duplicate tags?
		$tag = str_replace(array('"',"'"),array('',''),trim($v));
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

function update_task_tags($id, $tag_ids)
{
	global $db;
	foreach($tag_ids as $v) {
		$db->ex("INSERT INTO tag2task (task_id,tag_id) VALUES ($id,$v)");
	}
	$db->ex("UPDATE tags SET tags_count=tags_count+1 WHERE id IN (". implode(',', $tag_ids). ")");
}

### end 1.1-1.2 #####

?>