<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2011,2020-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

// Minimal supported version of Mysql is 5.7.9
// Minimal supported version of Maria DB is 10.2.2
// Minimal supported version of Postgres is 10

// Can be used to upgrade database from myTinyTodo v1.7 or later
$lastVer = '2.0';

if (PHP_VERSION_ID < 70400) {
    die("PHP 7.4 or above is required");
}

if (getenv('MTT_ENABLE_DEBUG') == 'YES') {
    set_exception_handler('debugExceptionHandler');
    define('MTT_DEBUG', true);
}
else {
    set_exception_handler('myExceptionHandler');
    define('MTT_DEBUG', false);
    #ini_set('zend.exception_ignore_args', 1); // php 7.4+
}

if (!defined('MTTPATH')) define('MTTPATH', dirname(__FILE__) .'/');
if (!defined('MTTINC'))  define('MTTINC', MTTPATH. 'includes/');
require_once(MTTINC. 'vars.php');
require_once(MTTINC. 'common.php');
require_once(MTTINC. 'class.dbconnection.php');
require_once(MTTINC. 'class.config.php');

$db = null;
$ver = '';
$error = '';
$dbtype = '';

$csrfToken = setupToken();
if ($csrfToken == '' || strlen($csrfToken) != 48) {
    $csrfToken = setSetupToken();
}
$csrfToken = htmlspecialchars($csrfToken);

$mttVersion = htmlspecialchars(MTTVersion::VERSION);

echo <<<EOD
<html>
<head>
  <meta name='robots' content='noindex,nofollow'>
  <title>myTinyTodo Setup (v$mttVersion )</title>
</head>
<body>
<h1>myTinyTodo Setup <span class=version>v$mttVersion</span></h1>
EOD;


$configExists = file_exists(MTTPATH. 'config.php');
if ($configExists)
{
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
    else if (version_compare($ver, '1.7') < 0) {
        // Old or previously failed while install
        exitMessage(htmlspecialchars("Can not update. Unsupported database version ($ver)."));
    }
    else {
        // 1.7, 1.8
        $dontStartSession = true;
    }

    require_once('./init.php');

    if ($ver != '' && $dontStartSession) {
        askPasswordV18($csrfToken);
    }
    else if ( !Config::$noDatabase && !is_logged() ) {
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
                    <input type=hidden name=stoken value='$csrfToken'>
                    <input type=hidden name=install value=create>
                    <input type=submit value=' Next '></form>");
    }
    elseif ($install == '')
    {
        # Specify database type and connection settings to save.
        exitMessage("
            <form method=post>Select database type to use:<br><br>
            <input type=hidden name=install value=config>
            <input type=hidden name=stoken value='$csrfToken'>
            <label><input type=radio name=db_type value=sqlite checked=checked onclick=\"document.getElementById('dbsettings').style.display='none'\"> SQLite</label><br><br>
            <label><input type=radio name=db_type value=mysql onclick=\"document.getElementById('dbsettings').style.display=''\"> MySQL</label><br><br>
            <label><input type=radio name=db_type value=postgres onclick=\"document.getElementById('dbsettings').style.display=''\"> PostgreSQL</label><br>
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
        checkSetupToken();
        # Save configuration
        $dbtype = $_POST['db_type'] ?? '';
        if (!in_array($dbtype, ['sqlite', 'mysql', 'postgres'])) {
            exitMessage("Unknown database type $dbtype");
        }
        SetupDbConfig::set('db.type', $dbtype);
        if ($dbtype == 'mysql' || $dbtype == 'postgres') {
            SetupDbConfig::set('db.host', _post('db_host'));
            SetupDbConfig::set('db.name', _post('db_name'));
            SetupDbConfig::set('db.user', _post('db_user'));
            SetupDbConfig::set('db.password', _post('db_password'));
            SetupDbConfig::set('db.prefix', trim(_post('db_prefix')));
        }
        SetupDbConfig::defineDbConstants();
        $db = testConnect($error);
        if (!$db) {
            exitMessage("Database connection error: ". htmlspecialchars($error));
        }
        if (defined('MTT_DB_DRIVER')) {
            SetupDbConfig::set('db.driver', MTT_DB_DRIVER);
        }
        tryToSaveDBConfig();
        exitMessage("<form method=post> This will create myTinyTodo database <br><br>
                <input type=hidden name=install value=create>
                <input type=hidden name=stoken value='$csrfToken'>
                <input type=submit value=' Install '></form>");
    }
    elseif ($install == 'create')
    {
        checkSetupToken();
        # install database
        createAllTables($db, $dbtype);  # throws

        # create user without a password
        $db->ex( "INSERT INTO {$db->prefix}users (id,username,name) VALUES (?,?,?)",
            array(1, "admin", "admin") );

        # create default list
        $db->ex( "INSERT INTO {$db->prefix}lists (user_id,uuid,name,d_created,taskview) VALUES (?,?,?,?,?)",
            array(1, generateUUID(), 'Todo', time(), 1) );
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
    if (!in_array($ver, array('1.8', '1.7'))) {
        exitMessage(htmlspecialchars("Can not update. Unsupported database version ($ver)."));
    }

    if (!isset($_POST['update'])) {
        exitMessage(htmlspecialchars("Update database v$ver to v$lastVer"). "<br><br>
            <form name=frm method=post>
            <input type=hidden name=update value=1>
            <input type=hidden name=stoken value='$csrfToken'>
            <input type=submit value=' Update '>
            </form>");
    }

    # update process
    checkSetupToken();
    if ($ver == '1.7') {
        update_17_18($db, $dbtype);
        update_18_20($db, $dbtype);
    }
    else if ($ver == '1.8') {
        update_18_20($db, $dbtype);
    }
}

echo "Done<br><br> <b>Attention!</b> Delete this file for security reasons. <br><br> Go to <a href='". htmlspecialchars(url_dir(getRequestUri())). "'>homepage</a>.";
printFooter();


function setupToken()
{
    return $_COOKIE['mtt-s-token'] ?? '';
}

function setSetupToken() : string
{
    $token = bin2hex(random_bytes(24));
    setcookie('mtt-s-token', $token, [
        'path' => url_dir(getRequestUri()),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    $_COOKIE['mtt-s-token'] = $token;
    return $token;
}

function checkSetupToken()
{
    $token = $_POST['stoken'] ?? '';
    if ( $token == '' || $token != setupToken() ) {
        die("Access denied! No token provided.");
    }
}

function askPasswordV18(string $csrfToken)
{
    $passhash = Config::get('password');
    if ($passhash == '') {
        return;
    }

    if (isset($_POST['configpassword'])) {
        checkSetupToken();
    }
    if (isset($_COOKIE['mtt-v18token'])) {
        if (validateTokenV18(['password'=>$passhash], $_COOKIE['mtt-v18token'])) {
            return; //authorized
        }
        if (MTT_DEBUG)
            error_log("Failed validation of v18token");
    }

    if ( !isset($_POST['configpassword']) || !isPasswordEqualsToHash($_POST['configpassword'], $passhash) ) {
        exitMessage("Enter current password to continue.
            <form method=post><input type=hidden name=stoken value='$csrfToken'>
            <input type=password name=configpassword> <input type=submit value=' Continue '></form>");
    }
    $token = generateTokenV18(['password'=>$passhash]);

    setcookie('mtt-v18token', $token, [
        'path' => url_dir(getRequestUri()),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

function validateTokenV18(
    #[SensitiveParameter]
    array $config,
    string $token) : bool
{
    if (!isset($config['password']) || $config['password'] == '') {
        return true;
    }
    $parts = explode('.', $token);
    if (count($parts) != 2) {
        return false;
    }
    $signature = base64_decode($parts[1]); //binary
    if ($signature === false) {
        return false;
    }
    if ( !hash_equals($signature, hash_hmac('sha256', $parts[0], $config['password'], true)) ) {
        return false;
    }
    $payload = json_decode(base64_decode($parts[0]), true);
    if (!isset($payload['exp']) || time() > $payload['exp']) {
        return false;
    }
    return true;
}

function generateTokenV18(
    #[SensitiveParameter]
    array $config ) : ?string
{
    if (!isset($config['password']) || $config['password'] == '') {
        return null;
    }
    $payload = base64_encode(json_encode([
        'exp' => time() + 120   # 2 min lifetime
    ]));
    return $payload. '.'. base64_encode(hash_hmac('sha256', $payload, $config['password'], true));
}


function databaseVersion(AbstractDatabase $db): string
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
    if ( $db->tableFieldExists($db->prefix.'todolist', 'tags') ) return $v; # field was removed in v1.8
    $v = '1.8';
    if ( !$db->tableExists($db->prefix.'users') ) return $v;
    $v = '2.0';
    return $v;
}

function hasMysqlUnicode520(AbstractDatabase $db): bool
{
    $r = $db->sq("SHOW COLLATION WHERE Charset='utf8mb4' and Collation='utf8mb4_unicode_520_ci'");
    return $r ? true : false;
}

function exitMessage(string $s)
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
            htmlspecialchars(SetupDbConfig::dbConfigAsFileContents()).
            "</textarea>\n".
            "<script type='text/javascript'>document.getElementById('contents').select();</script>"
        );
    }
    SetupDbConfig::saveDbConfig();
}

function testConnect(string &$error): ?AbstractDatabase
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
                    $db = new MysqliDatabase();
                }
                else {
                    throw new Exception("Required PHP extension 'MySQLi' is not installed.");
                }
            }
            else {
                if ($hasPDO) {
                    require_once(MTTINC. 'class.db.mysql.php');
                    if (!defined('MTT_DB_DRIVER')) define('MTT_DB_DRIVER', ''); // set pdo?
                    $db = new MysqlDatabase();
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

            $db = new PostgresDatabase;
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
            require_once(MTTINC. 'class.db.sqlite.php');
            $db = new SqliteDatabase;
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

function databaseTypeName(AbstractDatabase $db): string
{
    switch ($db::DBTYPE) {
        case DBConnection::DBTYPE_MYSQL: return "MySQL";
        case DBConnection::DBTYPE_POSTGRES: return "PostgreSQL";
        case DBConnection::DBTYPE_SQLITE: return "SQLite";
        default: throw new Exception("Unsupported database type: ". $db::DBTYPE);
    }
}



function createAllTables(AbstractDatabase $db, string $dbtype): void
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



/* ===== mysql ============================================================= */

function createMysqlTables(AbstractDatabase $db)
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
    `user_id` INT UNSIGNED NOT NULL default 0,
    `d_created` BIGINT UNSIGNED NOT NULL default 0,
    `d_edited` BIGINT UNSIGNED NOT NULL default 0,
    `sorting` TINYINT UNSIGNED NOT NULL default 0,
    `published` TINYINT UNSIGNED NOT NULL default 0,
    `taskview` INT UNSIGNED NOT NULL default 0,
    `extra` TEXT default NULL,
    PRIMARY KEY(`id`),
    UNIQUE KEY(`uuid`),
    KEY(`user_id`)
) CHARSET=utf8mb4 COLLATE $collation ");


    $db->ex(
"CREATE TABLE {$db->prefix}todolist (
    `id` INT UNSIGNED NOT NULL auto_increment,
    `uuid` CHAR(36) CHARACTER SET latin1 NOT NULL default '',
    `list_id` INT UNSIGNED NOT NULL default 0,
    `parent_id` INT UNSIGNED NOT NULL default 0,
    `d_created` BIGINT UNSIGNED NOT NULL default 0,   /* time() timestamp */
    `d_completed` BIGINT UNSIGNED NOT NULL default 0, /* time() timestamp */
    `d_edited` BIGINT UNSIGNED NOT NULL default 0,    /* time() timestamp */
    `compl` TINYINT UNSIGNED NOT NULL default 0,
    `title` VARCHAR(250) NOT NULL,
    `note` MEDIUMTEXT default NULL,
    `prio` TINYINT NOT NULL default 0,          /* priority -,0,+ */
    `ow` INT NOT NULL default 0,                /* order weight */
    `duedate` DATE default NULL,
    `extra` TEXT default NULL,
    PRIMARY KEY(`id`),
    KEY(`list_id`),
    UNIQUE KEY(`uuid`)
) CHARSET=utf8mb4 COLLATE $collation ");

    // Max length of varchar of utf8mb4 with UNIQUE index is 191 until Mysql 5.7.9 and MariaDB 10.2.2
    // since then innodb_default_row_format = DYNAMIC, https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-9.html
    $db->ex(
"CREATE TABLE {$db->prefix}tags (
    `id` INT UNSIGNED NOT NULL auto_increment,
    `user_id` INT UNSIGNED NOT NULL default 0,
    `name` VARCHAR(250) NOT NULL default '',
    PRIMARY KEY(`id`),
    UNIQUE KEY `name` (`name`),
    KEY(`user_id`)
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
"CREATE TABLE {$db->prefix}users (
    `id` INT UNSIGNED NOT NULL auto_increment,
    `username` VARCHAR(250) NOT NULL default '',
    `email` VARCHAR(250) NOT NULL default '',
    `name` VARCHAR(250) NOT NULL default '',
    `pwhash` VARCHAR(250) NOT NULL default '',
    `pwtoken` VARCHAR(250) NOT NULL default '',
    `last_visit` DATE default NULL,
    `extra` TEXT default NULL,
    PRIMARY KEY(`id`),
    UNIQUE KEY (`username`),
    UNIQUE KEY (`email`)
) CHARSET=utf8mb4 COLLATE $collation ");


    $db->ex(
"CREATE TABLE {$db->prefix}usersettings (
    `user_id` INT UNSIGNED NOT NULL default 0,
    `param_key` VARCHAR(250) NOT NULL default '',
    `param_value` TEXT default NULL,
    UNIQUE KEY (`user_id`, `param_key`)
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
    `last_access` BIGINT UNSIGNED NOT NULL default 0,  /* time() timestamp */
    `expires`     BIGINT UNSIGNED NOT NULL default 0,  /* time() timestamp */
    UNIQUE KEY `id` (`id`)
) CHARSET=utf8mb4 COLLATE $collation ");
}



/* ===== postgres ========================================================= */

function createPostgresTables(AbstractDatabase $db)
{
    $db->ex(
"CREATE TABLE {$db->prefix}lists (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    uuid CHAR(36) NOT NULL default '',
    ow INTEGER NOT NULL default 0,
    name VARCHAR(250) NOT NULL default '',
    user_id INTEGER NOT NULL default 0,
    d_created BIGINT NOT NULL default 0,
    d_edited BIGINT NOT NULL default 0,
    sorting SMALLINT NOT NULL default 0,
    published SMALLINT NOT NULL default 0,
    taskview INTEGER NOT NULL default 0,
    extra TEXT
) ");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}lists_uuid ON {$db->prefix}lists (uuid)");
    $db->ex("CREATE INDEX {$db->prefix}lists_user_id ON {$db->prefix}lists (user_id)");

    $db->ex(
"CREATE TABLE {$db->prefix}todolist (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    uuid CHAR(36) NOT NULL default '',
    list_id INTEGER NOT NULL default 0,
    parent_id INTEGER NOT NULL default 0,
    d_created BIGINT NOT NULL default 0,
    d_completed BIGINT NOT NULL default 0,
    d_edited BIGINT NOT NULL default 0,
    compl SMALLINT NOT NULL default 0,
    title VARCHAR(250) NOT NULL default '',
    note TEXT default NULL,
    prio SMALLINT NOT NULL default 0,
    ow INTEGER NOT NULL default 0,
    duedate DATE default NULL,
    extra TEXT default NULL
) ");
    $db->ex("CREATE INDEX {$db->prefix}todo_list_id ON {$db->prefix}todolist (list_id)");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}todo_uuid ON {$db->prefix}todolist (uuid)");

    $db->ex(
"CREATE TABLE {$db->prefix}tags (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    user_id INTEGER NOT NULL default 0,
    name VARCHAR(250) NOT NULL DEFAULT ''
) ");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}tags_lower_name ON {$db->prefix}tags ((LOWER(name)))");
    $db->ex("CREATE INDEX {$db->prefix}tags_user_id ON {$db->prefix}tags (user_id)");

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
"CREATE TABLE {$db->prefix}users (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    username VARCHAR(250) NOT NULL DEFAULT '',
    email VARCHAR(250) NOT NULL DEFAULT '',
    name VARCHAR(250) NOT NULL DEFAULT '',
    pwhash VARCHAR(250) NOT NULL DEFAULT '',
    pwtoken VARCHAR(250) NOT NULL DEFAULT '',
    last_visit DATE default NULL,
    extra TEXT default NULL
) ");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}users_lower_username ON {$db->prefix}users ((LOWER(username)))");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}users_lower_email ON {$db->prefix}users ((LOWER(email)))");


    $db->ex(
"CREATE TABLE {$db->prefix}usersettings (
    user_id INTEGER NOT NULL default 0,
    param_key VARCHAR(250) NOT NULL default '',
    param_value TEXT default NULL
) ");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}usersettings_ukey ON {$db->prefix}usersettings (user_id, param_key)");


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
    last_access BIGINT NOT NULL default 0,
    expires     BIGINT NOT NULL default 0
) ");
    $db->ex("CREATE UNIQUE INDEX {$db->prefix}sessions_id ON {$db->prefix}sessions (id)");
}



/* ===== sqlite ============================================================ */

function createSqliteTables(AbstractDatabase $db)
{
    $db->ex(
"CREATE TABLE {$db->prefix}lists (
    id INTEGER PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    ow INTEGER NOT NULL default 0,
    name VARCHAR(250) NOT NULL,
    user_id INTEGER UNSIGNED NOT NULL default 0,
    d_created INTEGER UNSIGNED NOT NULL default 0,
    d_edited INTEGER UNSIGNED NOT NULL default 0,
    sorting TINYINT UNSIGNED NOT NULL default 0,
    published TINYINT UNSIGNED NOT NULL default 0,
    taskview INTEGER UNSIGNED NOT NULL default 0,
    extra TEXT
) ");

    $db->ex("CREATE UNIQUE INDEX lists_uuid ON {$db->prefix}lists (uuid)");
    $db->ex("CREATE INDEX lists_user_id ON {$db->prefix}lists (user_id)");

    $db->ex(
"CREATE TABLE {$db->prefix}todolist (
    id INTEGER PRIMARY KEY,
    uuid CHAR(36) NOT NULL default '',
    list_id INTEGER UNSIGNED NOT NULL default 0,
    parent_id INTEGER UNSIGNED NOT NULL default 0,
    d_created INTEGER UNSIGNED NOT NULL default 0,
    d_completed INTEGER UNSIGNED NOT NULL default 0,
    d_edited INTEGER UNSIGNED NOT NULL default 0,
    compl TINYINT UNSIGNED NOT NULL default 0,
    title VARCHAR(250) NOT NULL default '',
    note TEXT default NULL,
    prio TINYINT NOT NULL default 0,
    ow INTEGER NOT NULL default 0,
    duedate DATE default NULL,
    extra TEXT default NULL
) ");
    $db->ex("CREATE INDEX todo_list_id ON {$db->prefix}todolist (list_id)");
    $db->ex("CREATE UNIQUE INDEX todo_uuid ON {$db->prefix}todolist (uuid)");


    $db->ex(
"CREATE TABLE {$db->prefix}tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNSIGNED NOT NULL default 0,
    name VARCHAR(250) NOT NULL DEFAULT ''
) ");
    $db->ex("CREATE INDEX tags_user_id ON {$db->prefix}tags (user_id)");


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
        "CREATE TABLE {$db->prefix}users (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        username   VARCHAR(250) NOT NULL DEFAULT '',
        email      VARCHAR(250) NOT NULL DEFAULT '',
        name       VARCHAR(250) NOT NULL DEFAULT '',
        pwhash     VARCHAR(250) NOT NULL DEFAULT '',
        pwtoken    VARCHAR(250) NOT NULL DEFAULT '',
        last_visit DATE default NULL,
        extra      TEXT default NULL
    ) ");
    $db->ex("CREATE UNIQUE INDEX users_username ON {$db->prefix}users (username COLLATE NOCASE)");
    $db->ex("CREATE UNIQUE INDEX users_email ON {$db->prefix}users (email COLLATE NOCASE)");


    $db->ex(
        "CREATE TABLE {$db->prefix}usersettings (
        user_id     INTEGER UNSIGNED NOT NULL default 0,
        param_key   VARCHAR(250) NOT NULL default '',
        param_value TEXT
    ) ");
    $db->ex("CREATE UNIQUE INDEX usersettings_ukey ON {$db->prefix}usersettings (user_id, param_key COLLATE NOCASE)");


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



### update v1.7 to v1.8 ##########
function update_17_18(AbstractDatabase $db, string $dbtype)
{
    $db->ex("BEGIN");

    if ($dbtype == 'sqlite')
    {
        // Use UTF8CI collate. Old sqlite does not support DROP COLUMN (before v3.35.0 2021-03-12, https://sqlite.org/releaselog/3_35_0.html)
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



function update_18_20(AbstractDatabase $db, string $dbtype)
{
    $db->ex("BEGIN");

    if ($dbtype == 'mysql')
    {
        $db->ex("ALTER TABLE {$db->prefix}lists MODIFY `d_created` BIGINT UNSIGNED NOT NULL default 0");
        $db->ex("ALTER TABLE {$db->prefix}lists MODIFY `d_edited` BIGINT UNSIGNED NOT NULL default 0");
        $db->ex("ALTER TABLE {$db->prefix}todolist MODIFY `d_created` BIGINT UNSIGNED NOT NULL default 0");
        $db->ex("ALTER TABLE {$db->prefix}todolist MODIFY `d_completed` BIGINT UNSIGNED NOT NULL default 0");
        $db->ex("ALTER TABLE {$db->prefix}todolist MODIFY `d_edited` BIGINT UNSIGNED NOT NULL default 0");
        $db->ex("ALTER TABLE {$db->prefix}todolist MODIFY `note` MEDIUMTEXT default NULL"); //upto 4mb in utf8mb4
        $db->ex("ALTER TABLE {$db->prefix}sessions MODIFY `last_access` BIGINT UNSIGNED NOT NULL default 0");
        $db->ex("ALTER TABLE {$db->prefix}sessions MODIFY `expires` BIGINT UNSIGNED NOT NULL default 0");

        $db->ex("ALTER TABLE {$db->prefix}lists ADD `user_id` INT UNSIGNED NOT NULL default 0");
        $db->ex("ALTER TABLE {$db->prefix}lists ADD KEY (`user_id`)");

        $db->ex("ALTER TABLE {$db->prefix}todolist ADD `parent_id` INT UNSIGNED NOT NULL default 0");
        $db->ex("ALTER TABLE {$db->prefix}todolist ADD `extra` TEXT default NULL");

        $db->ex("ALTER TABLE {$db->prefix}tags ADD `user_id` INT UNSIGNED NOT NULL default 0");
        $db->ex("ALTER TABLE {$db->prefix}tags ADD KEY (`user_id`)");

        $collation = 'utf8mb4_unicode_520_ci';

        $db->ex(
            "CREATE TABLE {$db->prefix}users (
                `id` INT UNSIGNED NOT NULL auto_increment,
                `username` VARCHAR(250) NOT NULL default '',
                `email` VARCHAR(250) NOT NULL default '',
                `name` VARCHAR(250) NOT NULL default '',
                `pwhash` VARCHAR(250) NOT NULL default '',
                `pwtoken` VARCHAR(250) NOT NULL default '',
                `last_visit` DATE default NULL,
                `extra` TEXT default NULL,
                PRIMARY KEY(`id`),
                UNIQUE KEY (`username`),
                UNIQUE KEY (`email`)
            ) CHARSET=utf8mb4 COLLATE $collation ");

        $db->ex(
            "CREATE TABLE {$db->prefix}usersettings (
                `user_id` INT UNSIGNED NOT NULL default 0,
                `param_key` VARCHAR(250) NOT NULL default '',
                `param_value` TEXT default NULL,
                UNIQUE KEY (`user_id`, `param_key`)
            ) CHARSET=utf8mb4 COLLATE $collation ");

    }
    else if ($dbtype == 'postgres')
    {
        $db->ex("ALTER TABLE {$db->prefix}lists ALTER d_created TYPE BIGINT");
        $db->ex("ALTER TABLE {$db->prefix}lists ALTER d_edited TYPE BIGINT");
        $db->ex("ALTER TABLE {$db->prefix}todolist ALTER d_created TYPE BIGINT");
        $db->ex("ALTER TABLE {$db->prefix}todolist ALTER d_completed TYPE BIGINT");
        $db->ex("ALTER TABLE {$db->prefix}todolist ALTER d_edited TYPE BIGINT");
        $db->ex("ALTER TABLE {$db->prefix}sessions ALTER last_access TYPE BIGINT");
        $db->ex("ALTER TABLE {$db->prefix}sessions ALTER expires TYPE BIGINT");

        $db->ex("ALTER TABLE {$db->prefix}todolist ADD parent_id INTEGER NOT NULL default 0");
        $db->ex("ALTER TABLE {$db->prefix}todolist ADD extra TEXT default NULL");

        $db->ex("ALTER TABLE {$db->prefix}lists ADD user_id INTEGER NOT NULL default 0");
        $db->ex("CREATE INDEX {$db->prefix}lists_user_id ON {$db->prefix}lists (user_id)");

        $db->ex("ALTER TABLE {$db->prefix}tags ADD user_id INTEGER NOT NULL default 0");
        $db->ex("CREATE INDEX {$db->prefix}tags_user_id ON {$db->prefix}tags (user_id)");

        $db->ex(
            "CREATE TABLE {$db->prefix}users (
                id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
                username VARCHAR(250) NOT NULL DEFAULT '',
                email VARCHAR(250) NOT NULL DEFAULT '',
                name VARCHAR(250) NOT NULL DEFAULT '',
                pwhash VARCHAR(250) NOT NULL DEFAULT '',
                pwtoken VARCHAR(250) NOT NULL DEFAULT '',
                last_visit DATE default NULL,
                extra TEXT default NULL
            ) ");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}users_lower_username ON {$db->prefix}users ((LOWER(username)))");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}users_lower_email ON {$db->prefix}users ((LOWER(email)))");

        $db->ex(
            "CREATE TABLE {$db->prefix}usersettings (
                user_id INTEGER NOT NULL default 0,
                param_key VARCHAR(250) NOT NULL default '',
                param_value TEXT default NULL
            ) ");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}usersettings_ukey ON {$db->prefix}usersettings (user_id, param_key)");

    }
    else if ($dbtype == 'sqlite')
    {
        // lists: add user_id column
        $db->ex("ALTER TABLE {$db->prefix}lists ADD user_id INTEGER UNSIGNED NOT NULL default 0");
        $db->ex("CREATE INDEX lists_user_id ON {$db->prefix}lists (user_id)");

        // todolist: remove collation from title and note column; add parent_id and extra columns
        $db->ex("DROP INDEX todo_list_id");
        $db->ex("DROP INDEX todo_uuid");
        $db->ex("ALTER TABLE {$db->prefix}todolist RENAME TO {$db->prefix}todolist_old");
        $db->ex(
            "CREATE TABLE {$db->prefix}todolist (
                id INTEGER PRIMARY KEY,
                uuid CHAR(36) NOT NULL default '',
                list_id INTEGER UNSIGNED NOT NULL default 0,
                parent_id INTEGER UNSIGNED NOT NULL default 0,
                d_created INTEGER UNSIGNED NOT NULL default 0,
                d_completed INTEGER UNSIGNED NOT NULL default 0,
                d_edited INTEGER UNSIGNED NOT NULL default 0,
                compl TINYINT UNSIGNED NOT NULL default 0,
                title VARCHAR(250) NOT NULL default '',
                note TEXT default NULL,
                prio TINYINT NOT NULL default 0,
                ow INTEGER NOT NULL default 0,
                duedate DATE default NULL,
                extra TEXT default NULL
        ) ");
        $db->ex("INSERT INTO {$db->prefix}todolist SELECT id,uuid,list_id,0,d_created,d_completed,d_edited,compl,title,note,prio,ow,duedate,null FROM {$db->prefix}todolist_old");
        $db->ex("CREATE INDEX todo_list_id ON {$db->prefix}todolist (list_id)");
        $db->ex("CREATE UNIQUE INDEX todo_uuid ON {$db->prefix}todolist (uuid)");
        $db->ex("DROP TABLE {$db->prefix}todolist_old");

        // tags: remove collation from name column; add user_id column
        $db->ex("DROP INDEX tags_name");
        $db->ex("ALTER TABLE {$db->prefix}tags RENAME TO {$db->prefix}tags_old");
        $db->ex(
            "CREATE TABLE {$db->prefix}tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER UNSIGNED NOT NULL default 0,
                name VARCHAR(250) NOT NULL DEFAULT ''
        ) ");
        $db->ex("INSERT INTO {$db->prefix}tags SELECT id,0,name FROM {$db->prefix}tags_old");
        $db->ex("DROP TABLE {$db->prefix}tags_old");
        $db->ex("CREATE INDEX tags_user_id ON {$db->prefix}tags (user_id)");


        $db->ex(
            "CREATE TABLE {$db->prefix}users (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                username   VARCHAR(250) NOT NULL DEFAULT '',
                email      VARCHAR(250) NOT NULL DEFAULT '',
                name       VARCHAR(250) NOT NULL DEFAULT '',
                pwhash     VARCHAR(250) NOT NULL DEFAULT '',
                pwtoken    VARCHAR(250) NOT NULL DEFAULT '',
                last_visit DATE default NULL,
                extra      TEXT default NULL
        ) ");
        $db->ex("CREATE UNIQUE INDEX users_username ON {$db->prefix}users (username COLLATE NOCASE)");
        $db->ex("CREATE UNIQUE INDEX users_email ON {$db->prefix}users (email COLLATE NOCASE)");

        $db->ex(
            "CREATE TABLE {$db->prefix}usersettings (
            user_id     INTEGER UNSIGNED NOT NULL default 0,
            param_key   VARCHAR(250) NOT NULL default '',
            param_value TEXT
        ) ");
        $db->ex("CREATE UNIQUE INDEX usersettings_ukey ON {$db->prefix}usersettings (user_id, param_key COLLATE NOCASE)");

    }

    $pwhash = (string)Config::get('password');
    $pwtoken = randomToken();
    $db->ex("INSERT INTO {$db->prefix}users (id,username,email,name,pwhash,pwtoken) VALUES (1,?,?,?,?,?)", [
        "admin", "admin", "admin", $pwhash, $pwtoken
    ]);

    $db->ex("UPDATE {$db->prefix}lists SET user_id = 1");
    $db->ex("UPDATE {$db->prefix}tags SET user_id = 1");

    $db->ex("INSERT INTO {$db->prefix}usersettings (user_id,param_key,param_value)
        SELECT 1,param_key,param_value FROM {$db->prefix}settings WHERE param_key='alltasks.json' ");
    $db->ex("DELETE FROM {$db->prefix}settings WHERE param_key='alltasks.json'");

    $db->ex("COMMIT");
}
### end of 2.0 #####
