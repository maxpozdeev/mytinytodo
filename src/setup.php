<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2011,2020-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

// Can be used to upgrade database from myTinyTodo v1.4 or later
$lastVer = '1.8';

if (version_compare(PHP_VERSION, '7.2.0') < 0) {
    die("PHP 7.2 or above is required");
}

if (getenv('MTT_ENABLE_DEBUG') == 'YES') {
    set_exception_handler('debugExceptionHandler');
    define('MTT_DEBUG', true);
}
else {
    set_exception_handler('myExceptionHandler');
    define('MTT_DEBUG', false);
}

if (!defined('MTTPATH')) define('MTTPATH', dirname(__FILE__) .'/');
if (!defined('MTTINC'))  define('MTTINC', MTTPATH. 'includes/');
require_once(MTTINC. 'common.php');
require_once(MTTINC. 'class.dbconnection.php');
require_once(MTTINC. 'class.config.php');
require_once(MTTINC. 'version.php');

$db = null;
$ver = '';
$error = '';

$setupToken = stoken();
if ($setupToken == '' || strlen($setupToken) != 36) {
    $setupToken = update_stoken();
}
$setupToken = htmlspecialchars($setupToken);

$configExists = file_exists(MTTPATH. 'config.php');
$oldConfigExists = file_exists(MTTPATH. 'db/config.php');


$mttVersion = htmlspecialchars(mytinytodo\Version::VERSION);
echo "<html><head><meta name='robots' content='noindex,nofollow'><title>myTinyTodo $mttVersion Setup</title></head><body>";
echo "<big><b>myTinyTodo $mttVersion Setup</b></big><br><br>";

if (!$configExists && $oldConfigExists)
{
    // First we need to migrate database config
    require_once(MTTPATH. 'db/config.php');
    if (isset($config['password']) && $config['password'] != '') {
        if (isset($_POST['configpassword'])) {
            check_post_stoken();
        }
        if ( !isset($_POST['configpassword']) || $_POST['configpassword'] != $config['password'] ) {
            exitMessage("Enter current password to continue. <form method=post><input type=hidden name=stoken value='$setupToken'><input type=password name=configpassword> <input type=submit value=' Continue '></form>");
        }
    }
    Config::loadConfigV14($config);
    tryToSaveDBConfig();
    $configExists = true;
}

if ($configExists)
{
    // No need to migrate database config
    require_once(MTTPATH. 'config.php');
    $db = testConnect($error);
    if (!$db) {
        exitMessage( "Database connection config file seems to be incorrect. You can remove config.php or edit it manually and then reload setup.<br><br>".
                     "<b>Error:</b> ". htmlspecialchars($error) );
    }
    // Config file v1.7 already exists and set up correctly
    $dbtype = MTT_DB_TYPE;

    // Determine current installed db version
    $ver = databaseVersion($db);
    if (MTT_DEBUG) {
        error_log("Database version detected: $ver");
    }

    if ($ver == '') {
        // Clean install
        // Don't load settings from database in init.php
        Config::$noDatabase = true;
    }
    else if (version_compare($ver, '1.4') < 0) {
        // Very old or previously failed while install
        exitMessage(htmlspecialchars("Can not update. Unsupported database version ($ver)."));
    }
    else if ($ver == '1.4') {
        // Need to upgrade. Do not ask for old password
        Config::$noDatabase = true; //don't load settings from db
        require_once(MTTPATH. 'db/config.php');
        Config::loadConfigV14($config);
        unset($config);
        DBConnection::init($db);
    }

    require_once('./init.php');
    if ( !is_logged() ) {
        die("Access denied!<br> Disable password protection or Log in.");
    }

}

if ($ver == '')
{
    $install = trim(_post('install'));

    if ($install == '' && $db !== null)
    {
        # We already have settings file and need to create tables.
        exitMessage("<form method=post>Click next to create tables in ". htmlspecialchars(databaseTypeName($db)). " database.<br><br>
                    <input type=hidden name=stoken value='$setupToken'>
                    <input type=hidden name=install value=create>
                    <input type=submit value=' Next '></form>");
    }
    elseif ($install == '')
    {
        # Specify database type and connection settings to save.
        exitMessage("
            <form method=post>Select database type to use:<br><br>
            <input type=hidden name=install value=config>
            <input type=hidden name=stoken value='$setupToken'>
            <label><input type=radio name=db_type value=sqlite checked=checked onclick=\"document.getElementById('dbsettings').style.display='none'\"> SQLite</label><br><br>
            <label><input type=radio name=db_type value=mysql onclick=\"document.getElementById('dbsettings').style.display=''\"> MySQL</label><br><br>
            <label><input type=radio name=db_type value=postgres onclick=\"document.getElementById('dbsettings').style.display=''\"> PostgreSQL (beta)</label><br>
            <div id='dbsettings' style='display:none; margin-left:30px;'><br><table>
            <tr><td>Host:</td><td><input name=db_host value=localhost></td></tr>
            <tr><td>Database:</td><td><input name=db_name value=mytinytodo></td></tr>
            <tr><td>User:</td><td><input name=db_user value=mtt></td></tr>
            <tr><td>Password:</td><td><input type=password name=db_password></td></tr>
            <tr><td>Table prefix:</td><td><input name=db_prefix value='mtt_'></td></tr>
            </table></div><br><input type=submit value=' Next '></form>
        ");
    }
    elseif ($install == 'config')
    {
        check_post_stoken();
        # Save configuration
        $dbtype = $_POST['db_type'] ?? '';
        if ($dbtype != 'mysql' && $dbtype != 'postgres' && $dbtype != 'sqlite') {
            exitMessage("Unknown database type $dbtype");
        }
        Config::set('db.type', $dbtype);
        if ($dbtype == 'mysql' || $dbtype == 'postgres') {
            Config::set('db.host', _post('db_host'));
            Config::set('db.name', _post('db_name'));
            Config::set('db.user', _post('db_user'));
            Config::set('db.password', _post('db_password'));
            Config::set('db.prefix', trim(_post('db_prefix')));
        }
        Config::defineDbConstants();
        $db = testConnect($error);
        if (!$db) {
            exitMessage("Database connection error: ". htmlspecialchars($error));
        }
        if (defined('MTT_DB_DRIVER')) {
            Config::set('db.driver', MTT_DB_DRIVER);
        }
        tryToSaveDBConfig();
        exitMessage("<form method=post> This will create myTinyTodo database <br><br>
                <input type=hidden name=install value=create>
                <input type=hidden name=stoken value='$setupToken'>
                <input type=submit value=' Install '></form>");
    }
    elseif ($install == 'create')
    {
        check_post_stoken();
        # install database
        createAllTables($db, $dbtype);  # throws

        # create default list
        $db->ex( "INSERT INTO {$db->prefix}lists (uuid,name,d_created,taskview) VALUES (?,?,?,?)", array(generateUUID(), 'Todo', time(), 1) );

        Config::save();
    }
    else {
        exitMessage("Unknown action");
    }
}
elseif ($ver == $lastVer)
{
    exitMessage("Installed version does not require database update.");
}
else
{
    if (!in_array($ver, array('1.4','1.7'))) {
        exitMessage(htmlspecialchars("Can not update. Unsupported database version ($ver)."));
    }

    if (!isset($_POST['update'])) {
        exitMessage(htmlspecialchars("Update database v$ver to v$lastVer"). "<br><br>
            <form name=frm method=post>
            <input type=hidden name=update value=1>
            <input type=hidden name=stoken value='$setupToken'>
            <input type=submit value=' Update '>
            </form>");
    }

    # update process
    check_post_stoken();
    if ($ver == '1.4') {
        update_14_17($db, $dbtype);
        update_17_18($db, $dbtype);
    }
    elseif ($ver == '1.7') {
        update_17_18($db, $dbtype);
    }
}

echo "Done<br><br> <b>Attention!</b> Delete this file for security reasons. <br><br> Go to <a href='". htmlspecialchars(url_dir(getRequestUri())). "'>homepage</a>.";
printFooter();


function stoken()
{
    return $_COOKIE['mtt-s-token'] ?? '';
}

function update_stoken()
{
    $token = generateUUID();
    if (PHP_VERSION_ID < 70300) {
        setcookie('mtt-s-token', $token, 0, url_dir(getRequestUri()). '; samesite=lax', '', false, true ) ;
    }
    else {
        setcookie('mtt-s-token', $token, [
            'path' => url_dir(getRequestUri()),
            'httponly' => true,
            'samesite' => 'lax'
        ]);
    }
    $_COOKIE['mtt-s-token'] = $token;
    return $token;
}
function check_post_stoken()
{
    $token = $_POST['stoken'] ?? '';
    if ( $token == '' || $token != stoken() ) {
        die("Access denied! No token provided.");
    }
}


function createAllTables($db, $dbtype)
{
    if ($dbtype == 'mysql') {
        createMysqlTables($db);
    }
    else if ($dbtype == 'postgres') {
        createPostgresTables($db);
    }
    else {
        createSqliteTables($db);
    }
}

/* ===== mysql ========================================================= */
function createMysqlTables(Database_Abstract $db)
{
    //$collation = hasMysqlUnicode520($db) ? 'utf8mb4_unicode_520_ci' : 'utf8mb4_unicode_ci';
    $collation = 'utf8mb4_unicode_520_ci';

    // Mysql does not support transactions while executing DDL
    $db->ex(
"CREATE TABLE {$db->prefix}lists (
    `id` INT UNSIGNED NOT NULL auto_increment,
    `uuid` CHAR(36) CHARACTER SET latin1 NOT NULL default '',
    `ow` INT NOT NULL default 0,
    `name` VARCHAR(250) NOT NULL default '',
    `d_created` INT UNSIGNED NOT NULL default 0,
    `d_edited` INT UNSIGNED NOT NULL default 0,
    `sorting` TINYINT UNSIGNED NOT NULL default 0,
    `published` TINYINT UNSIGNED NOT NULL default 0,
    `taskview` INT UNSIGNED NOT NULL default 0,
    `extra` TEXT,
    PRIMARY KEY(`id`),
    UNIQUE KEY(`uuid`)
) CHARSET=utf8mb4 COLLATE $collation ");


    $db->ex(
"CREATE TABLE {$db->prefix}todolist (
    `id` INT UNSIGNED NOT NULL auto_increment,
    `uuid` CHAR(36) CHARACTER SET latin1 NOT NULL default '',
    `list_id` INT UNSIGNED NOT NULL default 0,
    `d_created` INT UNSIGNED NOT NULL default 0,   /* time() timestamp */
    `d_completed` INT UNSIGNED NOT NULL default 0, /* time() timestamp */
    `d_edited` INT UNSIGNED NOT NULL default 0,    /* time() timestamp */
    `compl` TINYINT UNSIGNED NOT NULL default 0,
    `title` VARCHAR(250) NOT NULL,
    `note` TEXT,
    `prio` TINYINT NOT NULL default 0,          /* priority -,0,+ */
    `ow` INT NOT NULL default 0,                /* order weight */
    `duedate` DATE default NULL,
    PRIMARY KEY(`id`),
    KEY(`list_id`),
    UNIQUE KEY(`uuid`)
) CHARSET=utf8mb4 COLLATE $collation ");

    // Max length of varchar of utf8mb4 with UNIQUE index is 191 until Mysql 5.7 and MariaDB 10.2
    $db->ex(
"CREATE TABLE {$db->prefix}tags (
    `id` INT UNSIGNED NOT NULL auto_increment,
    `name` VARCHAR(250) NOT NULL default '',
    PRIMARY KEY(`id`),
    UNIQUE KEY `name` (`name`)
) CHARSET=utf8mb4 COLLATE $collation ");


    $db->ex(
"CREATE TABLE {$db->prefix}tag2task (
    `tag_id` INT UNSIGNED NOT NULL,
    `task_id` INT UNSIGNED NOT NULL,
    `list_id` INT UNSIGNED NOT NULL,
    KEY(`tag_id`),
    KEY(`task_id`),
    KEY(`list_id`)  /* for tagcloud */
) CHARSET=utf8mb4 COLLATE $collation ");


    $db->ex(
"CREATE TABLE {$db->prefix}settings (
    `param_key`   VARCHAR(250) CHARACTER SET latin1 NOT NULL default '',
    `param_value` TEXT,
    UNIQUE KEY `param_key` (`param_key`)
) CHARSET=utf8mb4 COLLATE $collation ");


    $db->ex(
"CREATE TABLE {$db->prefix}sessions (
    `id`          VARCHAR(64) CHARACTER SET latin1 NOT NULL default '',  /* upto 64 bytes for sha256 */
    `data`        TEXT,
    `last_access` INT UNSIGNED NOT NULL default 0,  /* time() timestamp */
    `expires`     INT UNSIGNED NOT NULL default 0,  /* time() timestamp */
    UNIQUE KEY `id` (`id`)
) CHARSET=utf8mb4 COLLATE $collation ");
}


/* ===== postgres =============================================== */
function createPostgresTables(Database_Abstract $db)
{
    $db->ex(
"CREATE TABLE {$db->prefix}lists (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    uuid CHAR(36) NOT NULL default '',
    ow INTEGER NOT NULL default 0,
    name VARCHAR(250) NOT NULL default '',
    d_created INTEGER NOT NULL default 0,
    d_edited INTEGER NOT NULL default 0,
    sorting SMALLINT NOT NULL default 0,
    published SMALLINT NOT NULL default 0,
    taskview INTEGER NOT NULL default 0,
    extra TEXT
) ");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}lists_uuid ON {$db->prefix}lists (uuid)");

    $db->ex(
"CREATE TABLE {$db->prefix}todolist (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    uuid CHAR(36) NOT NULL default '',
    list_id INTEGER NOT NULL default 0,
    d_created INTEGER NOT NULL default 0,
    d_completed INTEGER NOT NULL default 0,
    d_edited INTEGER NOT NULL default 0,
    compl SMALLINT NOT NULL default 0,
    title VARCHAR(250) NOT NULL default '',
    note TEXT default NULL,
    prio SMALLINT NOT NULL default 0,
    ow INTEGER NOT NULL default 0,
    duedate DATE default NULL
) ");
    $db->ex("CREATE INDEX {$db->prefix}todo_list_id ON {$db->prefix}todolist (list_id)");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}todo_uuid ON {$db->prefix}todolist (uuid)");

    $db->ex(
"CREATE TABLE {$db->prefix}tags (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    name VARCHAR(250) NOT NULL DEFAULT ''
) ");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}tags_lower_name ON {$db->prefix}tags ((LOWER(name)))");

    $db->ex(
"CREATE TABLE {$db->prefix}tag2task (
    tag_id INTEGER NOT NULL,
    task_id INTEGER NOT NULL,
    list_id INTEGER NOT NULL
) ");
    $db->ex("CREATE INDEX {$db->prefix}tag2task_tag_id ON {$db->prefix}tag2task (tag_id)");
    $db->ex("CREATE INDEX {$db->prefix}tag2task_task_id ON {$db->prefix}tag2task (task_id)");
    $db->ex("CREATE INDEX {$db->prefix}tag2task_list_id ON {$db->prefix}tag2task (list_id)");

    $db->ex(
"CREATE TABLE {$db->prefix}settings (
    param_key   VARCHAR(250) NOT NULL default '',
    param_value TEXT
) ");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}settings_key ON {$db->prefix}settings (param_key)");

    $db->ex(
"CREATE TABLE {$db->prefix}sessions (
    id          VARCHAR(64) NOT NULL default '',
    data        TEXT,
    last_access INTEGER NOT NULL default 0,
    expires     INTEGER NOT NULL default 0
) ");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}sessions_id ON {$db->prefix}sessions (id)");
}


/* ===== sqlite =============================================== */
function createSqliteTables(Database_Abstract $db)
{
    $db->ex(
"CREATE TABLE {$db->prefix}lists (
    id INTEGER PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    ow INTEGER NOT NULL default 0,
    name VARCHAR(250) NOT NULL,
    d_created INTEGER UNSIGNED NOT NULL default 0,
    d_edited INTEGER UNSIGNED NOT NULL default 0,
    sorting TINYINT UNSIGNED NOT NULL default 0,
    published TINYINT UNSIGNED NOT NULL default 0,
    taskview INTEGER UNSIGNED NOT NULL default 0,
    extra TEXT
) ");

    $db->ex("CREATE UNIQUE INDEX lists_uuid ON {$db->prefix}lists (uuid)");

    $db->ex(
"CREATE TABLE {$db->prefix}todolist (
    id INTEGER PRIMARY KEY,
    uuid CHAR(36) NOT NULL default '',
    list_id INTEGER UNSIGNED NOT NULL default 0,
    d_created INTEGER UNSIGNED NOT NULL default 0,
    d_completed INTEGER UNSIGNED NOT NULL default 0,
    d_edited INTEGER UNSIGNED NOT NULL default 0,
    compl TINYINT UNSIGNED NOT NULL default 0,
    title VARCHAR(250) NOT NULL default '' COLLATE UTF8CI,
    note TEXT COLLATE UTF8CI default NULL,
    prio TINYINT NOT NULL default 0,
    ow INTEGER NOT NULL default 0,
    duedate DATE default NULL
) ");
    $db->ex("CREATE INDEX todo_list_id ON {$db->prefix}todolist (list_id)");
    $db->ex("CREATE UNIQUE INDEX todo_uuid ON {$db->prefix}todolist (uuid)");


    $db->ex(
"CREATE TABLE {$db->prefix}tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(250) NOT NULL DEFAULT '' COLLATE UTF8CI
) ");
    $db->ex("CREATE INDEX tags_name ON {$db->prefix}tags (name)"); //NB: unique in mysql


    $db->ex(
"CREATE TABLE {$db->prefix}tag2task (
    tag_id INTEGER NOT NULL,
    task_id INTEGER NOT NULL,
    list_id INTEGER NOT NULL
) ");
    $db->ex("CREATE INDEX tag2task_tag_id ON {$db->prefix}tag2task (tag_id)");
    $db->ex("CREATE INDEX tag2task_task_id ON {$db->prefix}tag2task (task_id)");
    $db->ex("CREATE INDEX tag2task_list_id ON {$db->prefix}tag2task (list_id)");    /* for tagcloud */


    $db->ex(
"CREATE TABLE {$db->prefix}settings (
    param_key   VARCHAR(250) NOT NULL default '',
    param_value TEXT
) ");

    $db->ex("CREATE UNIQUE INDEX settings_key ON {$db->prefix}settings (param_key COLLATE NOCASE)");


    $db->ex(
"CREATE TABLE {$db->prefix}sessions (
    id          VARCHAR(64) NOT NULL default '',
    data        TEXT,
    last_access INTEGER UNSIGNED NOT NULL default 0,
    expires     INTEGER UNSIGNED NOT NULL default 0
) ");

    $db->ex("CREATE UNIQUE INDEX sessions_id ON {$db->prefix}sessions (id COLLATE NOCASE)");
}


function databaseVersion(Database_Abstract $db): string
{
    if ( !$db ) return '';
    if ( !$db->tableExists($db->prefix.'todolist') ) return '';
    $v = '1.0';
    if ( !$db->tableExists($db->prefix.'tags') ) return $v;
    $v = '1.1';
    if ( !$db->tableFieldExists($db->prefix.'todolist', 'duedate') ) return $v;
    $v = '1.2';
    if ( !$db->tableExists($db->prefix.'lists') ) return $v;
    $v = '1.3.0';
    if ( !$db->tableFieldExists($db->prefix.'todolist', 'd_completed') ) return $v;
    $v = '1.3.1';
    if ( !$db->tableFieldExists($db->prefix.'todolist', 'd_edited') ) return $v;
    $v = '1.4';
    if ( !$db->tableExists($db->prefix.'settings') ) return $v;
    $v = '1.7';
    if ( $db->tableFieldExists($db->prefix.'todolist', 'tags') ) return $v;
    $v = '1.8';
    return $v;
}

function hasMysqlUnicode520(Database_Abstract $db): bool
{
    $r = $db->sq("SHOW COLLATION WHERE Charset='utf8mb4' and Collation='utf8mb4_unicode_520_ci'");
    return $r ? true : false;
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

function tryToSaveDbConfig()
{
    if (!file_exists(MTTPATH.'config.php')) {
        @touch(MTTPATH.'config.php');
    }
    if (!is_writable(MTTPATH.'config.php')) {
        exitMessage("Database connection config file ('config.php') is not writable. You need to edit it manually, set contents to this and run setup once more. <br><br> \n".
            "<textarea id='contents' style='width:90%; min-height:300px;'>\n".
            htmlspecialchars(Config::dbConfigAsFileContents()).
            "</textarea>\n".
            "<script type='text/javascript'>document.getElementById('contents').select();</script>"
        );
    }
    Config::saveDbConfig();
}

function testConnect(&$error)
{
    $db = null;
    try
    {
        if (!defined('MTT_DB_TYPE')) {
            throw new Exception("MTT_DB_TYPE is not defined");
        }

        if (MTT_DB_TYPE == 'mysql')
        {
            $hasPDO = false;
            $hasMysqli = false;
            if (defined('PDO::MYSQL_ATTR_FOUND_ROWS')) {
                $hasPDO = true;
            }
            if (function_exists("mysqli_connect")) {
                $hasMysqli = true;
            }

            $driver = '';
            if (defined('MTT_DB_DRIVER')) {
                // forced to use specific mysql interface
                if ( in_array(MTT_DB_DRIVER, ['mysqli', 'pdo', '']) ) {
                    $driver = MTT_DB_DRIVER;
                    if ($driver == '') $driver = 'pdo'; // default
                }
                else {
                    throw new Exception("Unknown database driver");
                }
            }

            if ($driver == '') {
                // auto-detect driver
                if ($hasPDO) $driver = 'pdo';
                else if ($hasMysqli) $driver = 'mysqli';
            }

            $db = null;
            if ($driver == 'mysqli') {
                if ($hasMysqli) {
                    require_once(MTTINC. 'class.db.mysqli.php');
                    if (!defined('MTT_DB_DRIVER')) define('MTT_DB_DRIVER', 'mysqli');
                    $db = new Database_Mysqli();
                }
                else {
                    throw new Exception("Required PHP extension 'MySQLi' is not installed.");
                }
            }
            else {
                if ($hasPDO) {
                    require_once(MTTINC. 'class.db.mysql.php');
                    if (!defined('MTT_DB_DRIVER')) define('MTT_DB_DRIVER', ''); // set pdo?
                    $db = new Database_Mysql();
                }
                else {
                    throw new Exception("Required PHP extension 'PDO_MySQL' is not installed.");
                }
            }

            foreach (['MTT_DB_HOST', 'MTT_DB_USER', 'MTT_DB_PASSWORD', 'MTT_DB_NAME', 'MTT_DB_PREFIX'] as $c) {
                if (!defined($c)) throw new Exception("$c is not defined");
            }

            $db->connect([
                'host' => MTT_DB_HOST,
                'user' => MTT_DB_USER,
                'password' => MTT_DB_PASSWORD,
                'db' => MTT_DB_NAME
            ]);
        }
        else if (MTT_DB_TYPE == 'postgres')
        {
            if (!defined('PDO::PGSQL_ATTR_DISABLE_PREPARES')) {
                throw new Exception("Required PHP extension 'PDO_PostgreSQL' is not installed.");
            }
            require_once(MTTINC. 'class.db.postgres.php');

            foreach (['MTT_DB_HOST', 'MTT_DB_USER', 'MTT_DB_PASSWORD', 'MTT_DB_NAME', 'MTT_DB_PREFIX'] as $c) {
                if (!defined($c)) throw new Exception("$c is not defined");
            }

            $db = new Database_Postgres;
            $db->connect([
                'host' => MTT_DB_HOST,
                'user' => MTT_DB_USER,
                'password' => MTT_DB_PASSWORD,
                'db' => MTT_DB_NAME
            ]);
        }
        else if (MTT_DB_TYPE == 'sqlite')
        {
            if (false === $f = @fopen(MTTPATH. 'db/todolist.db', 'a+')) {
                throw new Exception("database file is not readable/writable");
            }
            else {
                fclose($f);
            }
            if (!is_writable(MTTPATH. 'db/')) {
                throw new Exception("database directory ('db') is not writable");
            }
            require_once(MTTINC. 'class.db.sqlite3.php');
            $db = new Database_Sqlite3;
            $db->connect([
                'filename' => MTTPATH. 'db/todolist.db'
            ]);
        }
        else {
            throw new Exception("Unsupported database type: ". MTT_DB_TYPE);
        }

        if (!defined('MTT_DB_PREFIX')) define('MTT_DB_PREFIX', '');
        $db->setPrefix(MTT_DB_PREFIX);
    }
    catch(Exception $e) {
        //if (MTT_DEBUG) throw $e;
        $error = $e->getMessage();
        return null;
    }
    $error = '';
    return $db;
}

function debugExceptionHandler(Throwable $e)
{
    echo '<br><b>Error ('. htmlspecialchars(get_class($e)) .'):</b> \''. htmlspecialchars($e->getMessage()) .'\' in <i>'. htmlspecialchars($e->getFile() .':'. $e->getLine()). '</i>'.
        "\n<pre>". htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    exit;
}

function myExceptionHandler(Throwable $e)
{
    $called = '';
    foreach ($e->getTrace() as $a) {
        if ($a['file'] == __FILE__) {
            $called = " in <i>". htmlspecialchars(basename($a['file']). ':'. $a['line']). "</i>";
            break;
        }
    }
    echo '<br><b>Error:</b> \''. htmlspecialchars($e->getMessage()). '\''. $called ;
    exit;
}

function databaseTypeName(Database_Abstract $db)
{
    switch ($db::DBTYPE) {
        case DBConnection::DBTYPE_MYSQL: return "MySQL";
        case DBConnection::DBTYPE_POSTGRES: return "PostgreSQL";
        case DBConnection::DBTYPE_SQLITE: return "SQLite";
        default: throw new Exception("Unsupported database type: ". $db::DBTYPE);
    }
}


### update v1.4 to v1.7 ##########
function update_14_17(Database_Abstract $db, $dbtype)
{
    $db->ex("BEGIN");

    if($dbtype=='mysql')
    {
        $db->ex("ALTER TABLE {$db->prefix}lists ADD `extra` TEXT");

        # increase the length of list and tag name
        # (not applicable to sqlite because it uses VARCHAR fields of any length as TEXT)
        $db->ex("ALTER TABLE {$db->prefix}todolist CHANGE `tags` `tags` VARCHAR(2000) NOT NULL default '' ");
        $db->ex("ALTER TABLE {$db->prefix}tags CHANGE `name` `name` VARCHAR(250) NOT NULL default '' ");
        $db->ex("ALTER TABLE {$db->prefix}lists CHANGE `name` `name` VARCHAR(250) NOT NULL default '' ");

        # convert charset to utf8mb4

        $db->ex("ALTER TABLE {$db->prefix}lists    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->ex("ALTER TABLE {$db->prefix}todolist CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->ex("ALTER TABLE {$db->prefix}tags     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->ex("ALTER TABLE {$db->prefix}tag2task CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        # create settings table

        $db->ex(
"CREATE TABLE {$db->prefix}settings (
 `param_key`   VARCHAR(250) NOT NULL default '',
 `param_value` TEXT,
UNIQUE KEY `param_key` (`param_key`)
) CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");

        # create sessions table

        $db->ex(
"CREATE TABLE {$db->prefix}sessions (
 `id`          VARCHAR(64) NOT NULL default '',
 `data`        TEXT,
 `last_access` INT UNSIGNED NOT NULL default 0,
 `expires`     INT UNSIGNED NOT NULL default 0,
UNIQUE KEY `id` (`id`)
) CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ");

    }

    else #sqlite
    {
        $db->ex("ALTER TABLE {$db->prefix}lists ADD extra TEXT");

        # settings

        $db->ex(
"CREATE TABLE {$db->prefix}settings (
 param_key   VARCHAR(100) NOT NULL default '',
 param_value TEXT
) ");
        $db->ex("CREATE UNIQUE INDEX settings_key ON {$db->prefix}settings (param_key COLLATE NOCASE)");

        # sessions

        $db->ex(
"CREATE TABLE {$db->prefix}sessions (
 id          VARCHAR(250) NOT NULL default '',
 data        TEXT,
 last_access INTEGER UNSIGNED NOT NULL default 0,
 expires     INTEGER UNSIGNED NOT NULL default 0
) ");

        $db->ex("CREATE UNIQUE INDEX sessions_id ON {$db->prefix}sessions (id COLLATE NOCASE)");
    }

    $db->ex("COMMIT");

    Config::save();
    Config::saveDbConfig();
}
### end of 1.7 #####

### update v1.7 to v1.8 ##########
function update_17_18(Database_Abstract $db, $dbtype)
{
    $db->ex("BEGIN");

    if ($dbtype == 'sqlite')
    {
        // Use UTF8CI collate. Old sqlite does not support DROP COLUMN (before v3.35.0 2021-03-12)
        $db->ex("DROP INDEX todo_list_id");
        $db->ex("DROP INDEX todo_uuid");
        $db->ex("ALTER TABLE {$db->prefix}todolist RENAME TO {$db->prefix}todolist_old");
        $db->ex(
            "CREATE TABLE {$db->prefix}todolist (
                id INTEGER PRIMARY KEY,
                uuid CHAR(36) NOT NULL default '',
                list_id INTEGER UNSIGNED NOT NULL default 0,
                d_created INTEGER UNSIGNED NOT NULL default 0,
                d_completed INTEGER UNSIGNED NOT NULL default 0,
                d_edited INTEGER UNSIGNED NOT NULL default 0,
                compl TINYINT UNSIGNED NOT NULL default 0,
                title VARCHAR(250) NOT NULL default '' COLLATE UTF8CI,
                note TEXT COLLATE UTF8CI default NULL,
                prio TINYINT NOT NULL default 0,
                ow INTEGER NOT NULL default 0,
                duedate DATE default NULL )"
        );
        $db->ex("INSERT INTO {$db->prefix}todolist SELECT id,uuid,list_id,d_created,d_completed,d_edited,compl,title,note,prio,ow,duedate FROM {$db->prefix}todolist_old");
        $db->ex("CREATE INDEX todo_list_id ON {$db->prefix}todolist (list_id)");
        $db->ex("CREATE UNIQUE INDEX todo_uuid ON {$db->prefix}todolist (uuid)");
        $db->ex("DROP TABLE {$db->prefix}todolist_old");

        $db->ex("DROP INDEX tags_name");
        $db->ex("ALTER TABLE {$db->prefix}tags RENAME TO {$db->prefix}tags_old");
        $db->ex(
            "CREATE TABLE {$db->prefix}tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(250) NOT NULL DEFAULT '' COLLATE UTF8CI )"
        );
        $db->ex("INSERT INTO {$db->prefix}tags SELECT * FROM {$db->prefix}tags_old");
        $db->ex("CREATE INDEX tags_name ON {$db->prefix}tags (name)");
        $db->ex("DROP TABLE {$db->prefix}tags_old");
    }
    else // mysql
    {
        $db->ex("ALTER TABLE {$db->prefix}todolist DROP COLUMN tags");
        $db->ex("ALTER TABLE {$db->prefix}todolist DROP COLUMN tags_ids");

        // if mysql db was created in v1.7.x then
        // tags.name field has length of 50 instead of 250,
        // settings.param_key field has length of 100 instead of 250
        $db->ex("ALTER TABLE {$db->prefix}lists MODIFY `uuid` CHAR(36) CHARACTER SET latin1 NOT NULL default '' ");
        $db->ex("ALTER TABLE {$db->prefix}lists MODIFY `name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL default '' ");
        $db->ex("ALTER TABLE {$db->prefix}lists MODIFY `extra` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ");

        $db->ex("ALTER TABLE {$db->prefix}todolist MODIFY `uuid` CHAR(36) CHARACTER SET latin1 NOT NULL default '' ");
        $db->ex("ALTER TABLE {$db->prefix}todolist MODIFY `title` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL default '' ");
        $db->ex("ALTER TABLE {$db->prefix}todolist MODIFY `note` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ");

        $db->ex("ALTER TABLE {$db->prefix}tags MODIFY `name` VARCHAR(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL default '' ");

        $db->ex("ALTER TABLE {$db->prefix}settings MODIFY `param_key` VARCHAR(250) CHARACTER SET latin1 NOT NULL default '' ");
        $db->ex("ALTER TABLE {$db->prefix}settings MODIFY `param_value` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ");

        $db->ex("ALTER TABLE {$db->prefix}sessions MODIFY `id` VARCHAR(64) CHARACTER SET latin1 NOT NULL default '' ");
        $db->ex("ALTER TABLE {$db->prefix}sessions MODIFY `data` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci ");
    }

    $db->ex("COMMIT");

    if ($dbtype == 'sqlite') {
        $db->ex("VACUUM");
    }


}
### end of 1.8 #####
