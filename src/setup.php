<?php

/*
	This file is part of myTinyTodo.
	(C) Copyright 2009-2011 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v2 license. See file COPYRIGHT for details.
*/

set_exception_handler('myExceptionHandler');

# Check old config file (prior v1.3)
require_once('./db/config.php');
if (!isset($config['db']))
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
}

if ($config['db'] != '')
{
	require_once('./includes/class.config.php');
	Config::$noDatabase = true; //will not load settings from database in init.php

	require_once('./init.php');
	if ( !is_logged() )
	{
		die("Access denied!<br> Disable password protection or Log in.");
	}
	$db = DBConnection::instance();
	$dbtype = (strtolower(get_class($db)) == 'database_mysql') ? 'mysql' : 'sqlite';
}
else
{
	if (!defined('MTTPATH')) define('MTTPATH', dirname(__FILE__) .'/');
	if (!defined('MTTINC'))  define('MTTINC', MTTPATH. 'includes/');
	require_once(MTTINC. 'common.php');
	require_once(MTTINC. 'class.dbconnection.php');
	require_once(MTTINC. 'class.config.php');
	Config::$noDatabase = true;
	Config::loadDbConfig($config);
	unset($config);

	$db = null;
	$dbtype = '';
}

$lastVer = '1.7';
echo '<html><head><meta name="robots" content="noindex,nofollow"><title>myTinyTodo @VERSION Setup</title></head><body>';
echo "<big><b>myTinyTodo @VERSION Setup</b></big><br><br>";

# determine current installed version
$ver = $db ? get_ver($db, $dbtype) : '';

if (!$ver)
{
	# Which DB to select
	if(!isset($_POST['installdb']) && !isset($_POST['install']))
	{
		exitMessage("<form method=post>Select database type to use:<br><br>
<label><input type=radio name=installdb value=sqlite checked=checked onclick=\"document.getElementById('mysqlsettings').style.display='none'\">SQLite</label><br><br>
<label><input type=radio name=installdb value=mysql onclick=\"document.getElementById('mysqlsettings').style.display=''\">MySQL</label><br>
<div id='mysqlsettings' style='display:none; margin-left:30px;'><br><table><tr><td>Host:</td><td><input name=mysql_host value=localhost></td></tr>
<tr><td>Database:</td><td><input name=mysql_db value=mytinytodo></td></tr>
<tr><td>User:</td><td><input name=mysql_user value=user></td></tr>
<tr><td>Password:</td><td><input type=password name=mysql_password></td></tr>
<tr><td>Table prefix:</td><td><input name=prefix value=\"mtt_\"></td></tr>
</table></div><br><input type=submit value=' Next '></form>");
	}
	elseif(isset($_POST['installdb']))
	{
		# Save configuration
		$dbtype = ($_POST['installdb'] == 'mysql') ? 'mysql' : 'sqlite';
		Config::set('db', $dbtype);
		if($dbtype == 'mysql') {
			Config::set('mysql.host', _post('mysql_host'));
			Config::set('mysql.db', _post('mysql_db'));
			Config::set('mysql.user', _post('mysql_user'));
			Config::set('mysql.password', _post('mysql_password'));
			Config::set('prefix', trim(_post('prefix')));
		}
		if(!testConnect($error)) {
			exitMessage("Database connection error: $error");
		}
		if(!is_writable('./db/config.php')) {
			exitMessage("Config file ('db/config.php') is not writable.");
		}
		Config::saveDbConfig();
		exitMessage("This will create myTinyTodo database <form method=post><input type=hidden name=install value=1><input type=submit value=' Install '></form>");
	}

	# install database
	if($dbtype == 'mysql')
	{
		try
		{

			$db->ex(
"CREATE TABLE {$db->prefix}lists (
 `id` INT UNSIGNED NOT NULL auto_increment,
 `uuid` CHAR(36) NOT NULL default '',
 `ow` INT NOT NULL default 0,
 `name` VARCHAR(50) NOT NULL default '',
 `d_created` INT UNSIGNED NOT NULL default 0,
 `d_edited` INT UNSIGNED NOT NULL default 0,
 `sorting` TINYINT UNSIGNED NOT NULL default 0,
 `published` TINYINT UNSIGNED NOT NULL default 0,
 `taskview` INT UNSIGNED NOT NULL default 0,
 PRIMARY KEY(`id`),
 UNIQUE KEY(`uuid`)
) CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci ");


			$db->ex(
"CREATE TABLE {$db->prefix}todolist (
 `id` INT UNSIGNED NOT NULL auto_increment,
 `uuid` CHAR(36) NOT NULL default '',
 `list_id` INT UNSIGNED NOT NULL default 0,
 `d_created` INT UNSIGNED NOT NULL default 0,   /* time() timestamp */
 `d_completed` INT UNSIGNED NOT NULL default 0, /* time() timestamp */
 `d_edited` INT UNSIGNED NOT NULL default 0,    /* time() timestamp */
 `compl` TINYINT UNSIGNED NOT NULL default 0,
 `title` VARCHAR(250) NOT NULL,
 `note` TEXT,
 `prio` TINYINT NOT NULL default 0,			/* priority -,0,+ */
 `ow` INT NOT NULL default 0,				/* order weight */
 `tags` VARCHAR(600) NOT NULL default '',	/* for fast access to task tags */
 `tags_ids` VARCHAR(250) NOT NULL default '', /* no more than 22 tags (x11 chars) */
 `duedate` DATE default NULL,
  PRIMARY KEY(`id`),
  KEY(`list_id`),
  UNIQUE KEY(`uuid`)
) CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci ");


			$db->ex(
"CREATE TABLE {$db->prefix}tags (
 `id` INT UNSIGNED NOT NULL auto_increment,
 `name` VARCHAR(50) NOT NULL,
 PRIMARY KEY(`id`),
 UNIQUE KEY `name` (`name`)
) CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci ");


			$db->ex(
"CREATE TABLE {$db->prefix}tag2task (
 `tag_id` INT UNSIGNED NOT NULL,
 `task_id` INT UNSIGNED NOT NULL,
 `list_id` INT UNSIGNED NOT NULL,
 KEY(`tag_id`),
 KEY(`task_id`),
 KEY(`list_id`)		/* for tagcloud */
) CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci ");


			$db->ex(
"CREATE TABLE {$db->prefix}settings (
 `param_key`   VARCHAR(100) NOT NULL default '',
 `param_value` TEXT,
UNIQUE KEY `param_key` (`param_key`)
) CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");

		} catch (Exception $e) {
			exitMessage("<b>Error:</b> ". htmlarray($e->getMessage()));
		}
	}
	else #sqlite
	{
		try
		{

			$db->ex(
"CREATE TABLE {$db->prefix}lists (
 id INTEGER PRIMARY KEY,
 uuid CHAR(36) NOT NULL,
 ow INTEGER NOT NULL default 0,
 name VARCHAR(50) NOT NULL,
 d_created INTEGER UNSIGNED NOT NULL default 0,
 d_edited INTEGER UNSIGNED NOT NULL default 0,
 sorting TINYINT UNSIGNED NOT NULL default 0,
 published TINYINT UNSIGNED NOT NULL default 0,
 taskview INTEGER UNSIGNED NOT NULL default 0
) ");

			$db->ex("CREATE UNIQUE INDEX lists_uuid ON {$db->prefix}lists (uuid)");

			$db->ex(
"CREATE TABLE {$db->prefix}todolist (
 id INTEGER PRIMARY KEY,
 uuid CHAR(36) NOT NULL,
 list_id INTEGER UNSIGNED NOT NULL default 0,
 d_created INTEGER UNSIGNED NOT NULL default 0,
 d_completed INTEGER UNSIGNED NOT NULL default 0,
 d_edited INTEGER UNSIGNED NOT NULL default 0,
 compl TINYINT UNSIGNED NOT NULL default 0,
 title VARCHAR(250) NOT NULL,
 note TEXT,
 prio TINYINT NOT NULL default 0,
 ow INTEGER NOT NULL default 0,
 tags VARCHAR(600) NOT NULL default '',
 tags_ids VARCHAR(250) NOT NULL default '',
 duedate DATE default NULL
) ");
			$db->ex("CREATE INDEX todo_list_id ON {$db->prefix}todolist (list_id)");
			$db->ex("CREATE UNIQUE INDEX todo_uuid ON {$db->prefix}todolist (uuid)");


			$db->ex(
"CREATE TABLE {$db->prefix}tags (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 name VARCHAR(50) NOT NULL COLLATE NOCASE
) ");
			$db->ex("CREATE UNIQUE INDEX tags_name ON {$db->prefix}tags (name COLLATE NOCASE)");


			$db->ex(
"CREATE TABLE {$db->prefix}tag2task (
 tag_id INTEGER NOT NULL,
 task_id INTEGER NOT NULL,
 list_id INTEGER NOT NULL
) ");
			$db->ex("CREATE INDEX tag2task_tag_id ON {$db->prefix}tag2task (tag_id)");
			$db->ex("CREATE INDEX tag2task_task_id ON {$db->prefix}tag2task (task_id)");
			$db->ex("CREATE INDEX tag2task_list_id ON {$db->prefix}tag2task (list_id)");	/* for tagcloud */


			$db->ex(
"CREATE TABLE {$db->prefix}settings (
 param_key   VARCHAR(100) NOT NULL default '',
 param_value TEXT
) ");

			$db->ex("CREATE UNIQUE INDEX settings_key ON {$db->prefix}settings (param_key COLLATE NOCASE)");

		} catch (Exception $e) {
			exitMessage("<b>Error:</b> ". htmlarray($e->getMessage()));
		}
	}

	# create default list
	$db->ex( "INSERT INTO {$db->prefix}lists (uuid,name,d_created,taskview) VALUES (?,?,?,?)", array(generateUUID(), 'Todo', time(), 1) );

	Config::save();
	Config::saveDbConfig();
}
elseif($ver == $lastVer)
{
	exitMessage("Installed version does not require database update.");
}
else
{
	if(!in_array($ver, array('1.4'))) {
		exitMessage("Can not update. Unsupported database version ($ver).");
	}
	if(!isset($_POST['update'])) {
		exitMessage("Update database v$ver to v$lastVer<br><br>
		<form name=frm method=post><input type=hidden name=update value=1><input type=hidden name=tz value=-1><input type=submit value=' Update '></form>
		<script type=\"text/javascript\">var tz = -1 * (new Date()).getTimezoneOffset(); document.frm.tz.value = tz;</script>
		");
	}

	# update process
	if ($ver == '1.4')
	{
		update_14_17($db, $dbtype);
	}
}
echo "Done<br><br> <b>Attention!</b> Delete this file for security reasons.";
printFooter();


function get_ver(Database_Abstract $db, $dbtype)
{
	if ( !$db || $dbtype == '' ) return '';
	if ( !$db->tableExists($db->prefix.'todolist') ) return '';
	$v = '1.0';
	if ( !$db->tableExists($db->prefix.'tags') ) return $v;
	$v = '1.1';
	if ( !db_has_field($dbtype, $db, $db->prefix.'todolist', 'duedate') ) return $v;
	$v = '1.2';
	if ( !$db->tableExists($db->prefix.'lists') ) return $v;
	$v = '1.3.0';
	if ( !db_has_field($dbtype, $db, $db->prefix.'todolist', 'd_completed') ) return $v;
	$v = '1.3.1';
	if ( !db_has_field($dbtype, $db, $db->prefix.'todolist', 'd_edited') ) return $v;
	$v = '1.4';
	if ( !$db->tableExists($db->prefix.'settings') ) return $v;
	$v = '1.7';
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

function db_has_field($dbtype, Database_Abstract $db, $table, $field)
{
	if ($dbtype == 'mysql') return has_field_mysql($db, $table, $field);
	elseif ($dbtype == 'sqlite') return has_field_sqlite($db, $table, $field);
	else throw new Exception("Unexpected database type");
}

function has_field_sqlite(Database_Abstract $db, $table, $field)
{
	$q = $db->dq("PRAGMA table_info(". $db->quote($table). ")");
	while($r = $q->fetchRow()) {
		if($r[1] == $field) return true;
	}
	return false;
}

function has_field_mysql(Database_Abstract $db, $table, $field)
{
	$q = $db->dq("DESCRIBE `$table`");
	while($r = $q->fetchRow()) {
		if($r[0] == $field) return true;
	}
	return false;
}

function testConnect(&$error)
{
	try
	{
		if(Config::get('db') == 'mysql')
		{
			if (defined('PDO::MYSQL_ATTR_FOUND_ROWS')) {
				require_once(MTTINC. 'class.db.mysql.php');
				Config::set('mysqli', 0);
			}
			else if (function_exists("mysqli_connect")) {
				require_once(MTTINC. 'class.db.mysqli.php');
				Config::set('mysqli', 1);
			}
			else {
				$text = "Required PHP extension 'PDO mysql' is not installed.";
				throw new Exception($text);
			}

			$db = new Database_Mysql;
			$db->connect(array(
				'host' => Config::get('mysql.host'),
				'user' => Config::get('mysql.user'),
				'password' => Config::get('mysql.password'),
				'db' => Config::get('mysql.db')
			));
		}
		else
		{
			if(false === $f = @fopen(MTTPATH. 'db/todolist.db', 'a+')) throw new Exception("database file is not readable/writable");
			else fclose($f);

			if(!is_writable(MTTPATH. 'db/')) throw new Exception("database directory ('db') is not writable");

			require_once(MTTINC. 'class.db.sqlite3.php');
			$db = new Database_Sqlite3;
			$db->connect( array( 'filename' => MTTPATH. 'db/todolist.db' ) );
		}
	} catch(Exception $e) {
		$error = $e->getMessage();
		return 0;
	}
	return 1;
}

function myExceptionHandler($e)
{
	echo '<br><b>Fatal Error:</b> \''. $e->getMessage() .'\' in <i>'. $e->getFile() .':'. $e->getLine() . '</i>'.
		"\n<pre>". $e->getTraceAsString() . "</pre>\n";
	exit;
}


### update v1.4 to v1.7 ##########
function update_14_17(Database_Abstract $db, $dbtype)
{
	$db->ex("BEGIN");
	if($dbtype=='mysql')
	{
		# convert charset to utf8mb4

		$db->ex("ALTER TABLE {$db->prefix}lists    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		$db->ex("ALTER TABLE {$db->prefix}todolist CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		$db->ex("ALTER TABLE {$db->prefix}tags     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
		$db->ex("ALTER TABLE {$db->prefix}tag2task CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

		# create settings table

		$db->ex(
"CREATE TABLE {$db->prefix}settings (
 `param_key`   VARCHAR(100) NOT NULL default '',
 `param_value` TEXT,
UNIQUE KEY `param_key` (`param_key`)
) CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");
	}

	else #sqlite
	{
		$db->ex(
"CREATE TABLE {$db->prefix}settings (
 param_key   VARCHAR(100) NOT NULL default '',
 param_value TEXT
) ");
		$db->ex("CREATE UNIQUE INDEX settings_key ON {$db->prefix}settings (param_key COLLATE NOCASE)");
	}
	$db->ex("COMMIT");

	Config::save();
	Config::saveDbConfig();
}
### end of 1.7 #####

?>