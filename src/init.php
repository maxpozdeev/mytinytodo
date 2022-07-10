<?php
/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2011,2019-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (version_compare(PHP_VERSION, '7.2.0') < 0) {
    die("PHP 7.2 or above is required");
}

if(!defined('MTTPATH')) define('MTTPATH', dirname(__FILE__) .'/');
if(!defined('MTTINC'))  define('MTTINC', MTTPATH. 'includes/');
if(!defined('MTTCONTENT'))  define('MTTCONTENT', MTTPATH. 'content/');
if(!defined('MTTLANG'))  define('MTTLANG', MTTCONTENT. 'lang/');
if(!defined('MTTTHEMES'))  define('MTTTHEMES', MTTCONTENT. 'themes/');

if (getenv('MTT_ENABLE_DEBUG') == 'YES') {
    define('MTT_DEBUG', true);
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
}
else {
    //ini_set('display_errors', '0');
    //ini_set('log_errors', '1');
    define('MTT_DEBUG', false);
}

requireConfig();
require_once(MTTINC. 'common.php');
require_once(MTTINC. 'class.dbconnection.php');
require_once(MTTINC. 'class.dbcore.php');
require_once(MTTINC. 'class.config.php');
require_once(MTTINC. 'version.php');


# MySQL Database Connection
if (MTT_DB_TYPE == 'mysql')
{
    if (defined('MTT_DB_DRIVER') && MTT_DB_DRIVER == 'mysqli') {
        require_once(MTTINC. 'class.db.mysqli.php');
        $db = new Database_Mysqli();
    }
    else {
        require_once(MTTINC. 'class.db.mysql.php');
        $db = new Database_Mysql();
    }
    DBConnection::init($db);
    try {
        $db->connect( array(
            'host' => MTT_DB_HOST,
            'user' => MTT_DB_USER,
            'password' => MTT_DB_PASSWORD,
            'db' => MTT_DB_NAME,
        ));
    }
    catch(Exception $e) {
        logAndDie("Failed to connect to mysql database: ". $e->getMessage());
    }
    $db->dq("SET NAMES utf8mb4");
}

# SQLite3 Database
elseif (MTT_DB_TYPE == 'sqlite')
{
    require_once(MTTINC. 'class.db.sqlite3.php');
    $db = DBConnection::init(new Database_Sqlite3);
    $db->connect( array( 'filename' => MTTPATH. 'db/todolist.db' ) );
}
else {
    die("Incorrect database connection config");
}

DBConnection::setTablePrefix(MTT_DB_PREFIX);
DBCore::setDefaultInstance(new DBCore($db));
Config::load();


date_default_timezone_set(Config::get('timezone'));

//User can override language setting by cookies or query
$forceLang = '';
if( isset($_COOKIE['lang']) ) $forceLang = $_COOKIE['lang'];
//else if ( isset($_GET['lang']) ) $forceLang = $_GET['lang'];

if ( $forceLang != '' && preg_match("/^[a-z-]+$/i", $forceLang) ) {
    Config::set('lang', $forceLang); //TODO: special for demo, do not change config
}

require_once(MTTINC. 'class.lang.php');
Lang::loadLang( Config::get('lang') );

$_mttinfo = array();

if (need_auth() && !isset($dontStartSession)) {
    setup_and_start_session();
}


function requireConfig()
{
    $exists = file_exists(MTTPATH. 'config.php');
    $defined = false;
    if ($exists) {
        require_once(MTTPATH. 'config.php');
        $defined = defined('MTT_DB_TYPE');
    }
    # It seems not installed
    if (!$defined) {
        die("Not installed. Run <a href=setup.php>setup.php</a> first.");
    }
}

function need_auth(): bool
{
    return (Config::get('password') != '') ? true : false;
}

function is_logged(): bool
{
    if ( !need_auth() ) return true;
    if ( !isset($_SESSION['logged']) || !isset($_SESSION['sign']) ) return false;
    if ( !(int)$_SESSION['logged'] ) return false;
    return isValidSignature($_SESSION['sign'], session_id(), Config::get('password'), defined('MTT_SALT') ? MTT_SALT : '');
}

function is_readonly(): bool
{
    if ( !is_logged() ) return true;
    return false;
}

function updateSessionLogged(bool $logged)
{
    if ($logged) {
        $_SESSION['logged'] = 1;
        $_SESSION['sign'] = idSignature(session_id(), Config::get('password'), defined('MTT_SALT') ? MTT_SALT : '');
    }
    else {
        unset($_SESSION['logged']);
        unset($_SESSION['sign']);
    }
}

function access_token(): string
{
    if (!need_auth()) return '';
    if (!isset($_SESSION)) return '';
    if (!isset($_SESSION['token'])) return '';
    return $_SESSION['token'];
}

function check_token()
{
    if (!need_auth()) return;
    $token = access_token();
    if ($token == '' || !isset($_SERVER['HTTP_MTT_TOKEN']) || $_SERVER['HTTP_MTT_TOKEN'] != $token) {
        http_response_code(403);
        die("Access denied! You must authenticate first.");
    }
}

function update_token(): string
{
    $_SESSION['token'] = generateUUID();
    return $_SESSION['token'];
}

function setup_and_start_session()
{
    require_once(MTTINC. 'class.sessionhandler.php');
    session_set_save_handler(new MTTSessionHandler());

    ini_set('session.use_cookies', true);
    ini_set('session.use_only_cookies', true);

    $lifetime = 5184000; # 60 days session cookie lifetime
    $path = url_dir(Config::get('url')=='' ? getRequestUri() : Config::getUrl('url'));
    $samesite = 'lax';

    if (PHP_VERSION_ID < 70300) {
        # this is a known samesite flag workaround, was fixed in 7.3
        session_set_cookie_params($lifetime, $path. '; samesite='.$samesite, null, null, true);
    } else {
        session_set_cookie_params(Array(
            'lifetime' => $lifetime,
            'path' => $path,
            'httponly' => true,
            'samesite' => $samesite
        ));
    }
    session_name('mtt-session');
    session_start();
}

function timestampToDatetime($timestamp)
{
    $format = Config::get('dateformat') .' '. (Config::get('clock') == 12 ? 'g:i A' : 'H:i');
    return formatTime($format, $timestamp);
}

function formatTime($format, $timestamp=0)
{
    $lang = Lang::instance();
    if($timestamp == 0) $timestamp = time();
    $newformat = strtr($format, array('F'=>'%1', 'M'=>'%2'));
    $adate = explode(',', date('n,'.$newformat, $timestamp), 2);
    $s = $adate[1];
    if($newformat != $format)
    {
        $am = (int)$adate[0];
        $ml = $lang->get('months_long');
        $ms = $lang->get('months_short');
        $F = $ml[$am-1];
        $M = $ms[$am-1];
        $s = strtr($s, array('%1'=>$F, '%2'=>$M));
    }
    return $s;
}

function _e($s)
{
    echo Lang::instance()->get($s);
}

function __($s)
{
    return Lang::instance()->get($s);
}

function mttinfo($v)
{
    echo get_mttinfo($v);
}

function get_mttinfo($v)
{
    return htmlspecialchars( get_unsafe_mttinfo($v) );
}

/*
 * Returned values from get_unsafe_mttinfo() can be unsafe for html.
 * But '\r' and '\n' in URLs taken from config are removed.
 */
function get_unsafe_mttinfo($v)
{
    global $_mttinfo;
    if (isset($_mttinfo[$v])) {
        return $_mttinfo[$v];
    }
    switch($v)
    {
        case 'template_url':
            $_mttinfo['template_url'] = get_unsafe_mttinfo('mtt_url'). 'content/themes/'. Config::get('template') . '/';
            return $_mttinfo['template_url'];
        case 'includes_url':
            $_mttinfo['includes_url'] = get_unsafe_mttinfo('mtt_url'). 'includes/';
            return $_mttinfo['includes_url'];
        case 'url':
            /* full url to homepage: directory with root index.php or custom index file in the root. */
            /* ex: http://my.site/mytinytodo/   or  https://my.site/mytinytodo/home_for_2nd_theme.php  */
            /* Should not contain a query string. Have to be set in config if custom port is used or wrong detection. */
            $_mttinfo['url'] = Config::getUrl('url');
            if ($_mttinfo['url'] == '') {
                $is_https = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? true : false;
                $_mttinfo['url'] = ($is_https ? 'https://' : 'http://'). $_SERVER['HTTP_HOST']. url_dir(getRequestUri());
            }
            return $_mttinfo['url'];
        case 'mtt_url':
            /* Directory with settings.php. No need to set if you use default directory structure. */
            $_mttinfo['mtt_url'] = Config::getUrl('mtt_url'); // need to have a trailing slash
            if ($_mttinfo['mtt_url'] == '') {
                $_mttinfo['mtt_url'] = url_dir( get_unsafe_mttinfo('url'), 0 );
            }
            return $_mttinfo['mtt_url'];
        case 'api_url':
            /* URL for API, like http://localhost/mytinytodo/api/. No need to set by default. */
            $_mttinfo['api_url'] = Config::getUrl('api_url'); // need to have a trailing slash
            if ($_mttinfo['api_url'] == '') {
                $_mttinfo['api_url'] = get_unsafe_mttinfo('mtt_url'). 'api/';
            }
            return $_mttinfo['api_url'];
        case 'title':
            $_mttinfo['title'] = (Config::get('title') != '') ? Config::get('title') : __('My Tiny Todolist');
            return $_mttinfo['title'];
        case 'version':
            if (MTT_DEBUG) {
                $_mttinfo['version'] = mytinytodo\Version::VERSION . '-' . time();
            } else {
                $_mttinfo['version'] = mytinytodo\Version::VERSION;
            }
            return $_mttinfo['version'];
        case 'appearance':
            $_mttinfo['appearance'] = Config::get('appearance');
            return $_mttinfo['appearance'];
    }
}

function reset_mttinfo($key)
{
    global $_mttinfo;
    unset( $_mttinfo[$key] );
}

function jsonExit($data)
{
    header('Content-type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('Pragma: no-cache'); // for old HTTP/1.0 intermediate caches
    header_remove('Expires');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function logAndDie($userText, $errText = null)
{
    $errText === null ? error_log($userText) : error_log($errText);
    if (ini_get('display_errors')) {
        echo htmlspecialchars($userText);
    }
    else {
        echo "Error! See details in error log.";
    }
    exit(1);
}
