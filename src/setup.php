<?php

/*
	This file is part of myTinyTodo.
	(C) Copyright 2009 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/

# Check old config file (prior v1.3)
require_once('./db/config.php');
if(!isset($config['db']))
{
	if(isset($config['mysql'])) {
		$config['db'] = 'mysql';
		$config['mysql.host'] = $config['mysql'][0];
		$config['mysql.db'] = $config['mysql'][3];
		$config['mysql.user'] = $config['mysql'][1];
		$config['mysql.password'] = $config['mysql'][2];
	} else {
		$config['db'] = 'sqlite';
	}
	if(isset($config['allow']) && $config['allow'] == 'read') $config['allowread'] = 1;
	#Config::save($config);
}

require_once('./init.php');
if($needAuth && !is_logged())
{
	die("Access denied!<br> Disable password protection or Log in.");
}
$dbclass = strtolower(get_class($db));
$dbtype = ($dbclass == 'database_mysql') ? 'mysql' : 'sqlite';

$lastVer = '1.3';
echo '<html><head><meta name="robots" content="noindex,nofollow"></head><body>'; 
echo "<big><b>myTinyTodo v$lastVer Setup</b></big><br><br>";

# determine current installed version
$ver = get_ver($db, $dbtype);

if(!$ver)
{
	# Which DB to select
	if(!isset($_POST['installdb']) && !isset($_POST['install']))
	{
		exitMessage("<form method=post>Select database type to use:<br><br>
<label><input type=radio name=installdb value=sqlite checked>SQLite</label><br><br>
<label><input type=radio name=installdb value=mysql>MySQL</label><br>
<table style=\"margin-left:30px\"><tr><td>Host:</td><td><input name=mysql_host value=localhost></td></tr>
<tr><td>Database:</td><td><input name=mysql_db value=mytinytodo></td></tr>
<tr><td>User:</td><td><input name=mysql_user value=user></td></tr>
<tr><td>Password:</td><td><input type=password name=mysql_password></td></tr>
</table><br><input type=submit value=' Next '></form>");
	}
	elseif(isset($_POST['installdb']))
	{
		# Save configuration
		$dbtype = ($_POST['installdb'] == 'mysql') ? 'mysql' : 'sqlite';
		$config['db'] = $dbtype;
		if($dbtype == 'mysql') {
			$config['mysql.host'] = _post('mysql_host');
			$config['mysql.db'] = _post('mysql_db');
			$config['mysql.user'] = _post('mysql_user');
			$config['mysql.password'] = _post('mysql_password');
		}
		if(!testConnect($error)) {
			exitMessage("Database connection error: $error");
		}
		Config::save($config);
		exitMessage("This will create myTinyTodo database <form method=post><input type=hidden name=install value=1><input type=submit value=' Install '></form>");
	}

	# install database
	if($dbtype == 'mysql') 
	{
		try
		{
			$db->ex(
"CREATE TABLE todolist (
 `id` INT UNSIGNED NOT NULL auto_increment,
 `d` DATETIME NOT NULL,
 `list_id` INT UNSIGNED NOT NULL default 0,
 `compl` TINYINT UNSIGNED NOT NULL default 0,
 `title` VARCHAR(250) NOT NULL,
 `note` TEXT,
 `prio` TINYINT NOT NULL default 0,			/* priority -,0,+ */
 `ow` INT NOT NULL default 0,				/* order weight */
 `tags` VARCHAR(250) NOT NULL default '',	/* denormalization - for fast access to task tags */
 `duedate` DATE default NULL,
  PRIMARY KEY(`id`),
  KEY(`list_id`)
) CHARSET=utf8 ");

			$db->ex(
"CREATE TABLE tags (
 `id` INT UNSIGNED NOT NULL auto_increment,
 `name` VARCHAR(50) NOT NULL,
 `tags_count` INT default 0,
 `list_id` INT UNSIGNED NOT NULL default 0,
 PRIMARY KEY(`id`),
 UNIQUE KEY `listid_nmae` (`list_id`,`name`)
) CHARSET=utf8 ");

			$db->ex(
"CREATE TABLE tag2task (
 `tag_id` INT UNSIGNED NOT NULL,
 `task_id` INT UNSIGNED NOT NULL,
 KEY(`tag_id`),
 KEY(`task_id`)
) CHARSET=utf8 ");

			$db->ex(
"CREATE TABLE lists (
 `id` INT UNSIGNED NOT NULL auto_increment,
 `name` VARCHAR(50) NOT NULL default '',
 PRIMARY KEY(`id`)
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
 list_id INTEGER UNSIGNED NOT NULL default 0,
 compl TINYINT UNSIGNED NOT NULL default 0,
 title VARCHAR(250) NOT NULL,
 note TEXT,
 prio TINYINT NOT NULL default 0,
 ow INT NOT NULL default 0,
 tags VARCHAR(250) NOT NULL default '',
 duedate DATE default NULL
) ");
			$db->ex("CREATE INDEX list_id ON todolist (list_id)");

			$db->ex(
"CREATE TABLE tags (
 id INTEGER PRIMARY KEY,
 name VARCHAR(50) NOT NULL,
 tags_count INT default 0,
 list_id INTEGER UNSIGNED NOT NULL default 0
) ");
			$db->ex("CREATE UNIQUE INDEX tags_listid_name ON tags (list_id,name COLLATE NOCASE)");

			$db->ex(
"CREATE TABLE tag2task (
 tag_id INTEGER NOT NULL,
 task_id INTEGER NOT NULL
) ");
			$db->ex("CREATE INDEX tag_id ON tag2task (tag_id)");
			$db->ex("CREATE INDEX task_id ON tag2task (task_id)");

			$db->ex(
"CREATE TABLE lists (
 id INTEGER PRIMARY KEY,
 name VARCHAR(50) NOT NULL
) ");

		} catch (Exception $e) {
			exitMessage("<b>Error:</b> ". htmlarray($e->getMessage()));
		} 
	}

	createDefaultList($db);
}
elseif($ver == $lastVer)
{
	exitMessage("Installed version (v$ver) does not require database update.");
}
else
{
	if(!in_array($ver, array('1.1','1.2'))) {
		exitMessage("Can not update database. Unsupported version (v$ver).");
	}
	if(!isset($_POST['update'])) {
		exitMessage("Update database v$ver <form method=post><input type=hidden name=update value=1><input type=submit value=' Update '></form>");
	}

	# update process
	if($ver == '1.2')
	{
		update_12_13($db, $dbtype);
	}
	elseif($ver == '1.1')
	{
		update_11_12($db, $dbtype);
		update_12_13($db, $dbtype);
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
	if(!$db->table_exists('lists')) return $v;
	$v = '1.3';
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

function testConnect(&$error)
{
	global $config;
	try {
		if($config['db'] == 'mysql')
		{
			require_once('class.db.mysql.php');
			$db = new Database_Mysql;
			$db->connect($config['mysql.host'], $config['mysql.user'], $config['mysql.password'], $config['mysql.db']);
		} else
		{
			if(false === $f = @fopen('./db/todolist.db', 'a+')) throw new Exception("database file is not readable/writable");
			else fclose($f);
		}
	} catch(Exception $e) {
		$error = $e->getMessage();
		return 0;
	}
	return 1;
}

### 1.1-1.2 ##########
function update_11_12($db, $dbtype)
{
	if($dbtype == 'mysql') $db->ex("ALTER TABLE todolist ADD `duedate` DATE default NULL");
	else $db->ex("ALTER TABLE todolist ADD duedate DATE default NULL");

	# Fixing broken tags
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

### 1.2-1.3 ##########
function update_12_13($db, $dbtype)
{
	# update config
	global $config;
	Config::save($config);

	# and then db
	$db->ex("BEGIN");
	if($dbtype=='mysql')
	{
		$db->ex(
"CREATE TABLE lists (
 `id` INT UNSIGNED NOT NULL auto_increment,
 `name` VARCHAR(50) NOT NULL default '',
 PRIMARY KEY(`id`)
) CHARSET=utf8 ");
		$db->ex("ALTER TABLE todolist ADD `list_id` INT UNSIGNED NOT NULL default 0");
		$db->ex("ALTER TABLE tags ADD `list_id` INT UNSIGNED NOT NULL default 0");

		$db->ex("ALTER TABLE todolist ADD KEY(`list_id`)");
		$db->ex("DROP INDEX `name` ON tags");
		$db->ex("ALTER TABLE tags ADD UNIQUE KEY `listid_name` (`list_id`,`name`)");
	}
	else
	{
		$db->ex(
"CREATE TABLE lists (
 id INTEGER PRIMARY KEY,
 name VARCHAR(50) NOT NULL
) ");
		$db->ex("ALTER TABLE todolist ADD list_id INTEGER UNSIGNED NOT NULL default 0");
		$db->ex("CREATE INDEX todolist_list_id ON todolist (list_id)");
		
		$db->ex(
"CREATE TEMPORARY TABLE tags_backup (
 id INTEGER,
 name VARCHAR(50) NOT NULL,
 tags_count INT default 0
) ");		
		$db->ex("INSERT INTO tags_backup SELECT id,name,tags_count FROM tags");
		$db->ex("DROP TABLE tags");
		$db->ex(
"CREATE TABLE tags (
 id INTEGER PRIMARY KEY,
 name VARCHAR(50) NOT NULL,
 tags_count INT default 0,
 list_id INTEGER UNSIGNED NOT NULL default 0
) ");
		$db->ex("INSERT INTO tags (id,name,tags_count) SELECT id,name,tags_count FROM tags_backup");
		$db->ex("CREATE UNIQUE INDEX tags_listid_name ON tags (list_id,name COLLATE NOCASE) ");
		$db->ex("DROP TABLE tags_backup");
	}
	$db->ex("COMMIT");

	createDefaultList($db);
}

function createDefaultList($db)
{
	$db->ex("INSERT INTO lists (name) VALUES (?)", array('Todo'));

	$db->ex("UPDATE todolist SET list_id=1");
	$db->ex("UPDATE tags SET list_id=1");
}

### end 1.2-1.3 #####

?>