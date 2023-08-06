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
if(!defined('MTT_CONTENT_PATH')) define('MTT_CONTENT_PATH', MTTPATH. 'content/');

requireConfig();

if (!defined('MTT_THEME')) {
    define('MTT_THEME', 'theme');
}
define('MTT_THEME_PATH', MTT_CONTENT_PATH. MTT_THEME. '/');


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

require_once(MTTINC. 'common.php');
require_once(MTTINC. 'classes.php');
require_once(MTTINC. 'version.php');
require_once(MTTINC. 'class.dbconnection.php');
require_once(MTTINC. 'class.dbcore.php');
require_once(MTTINC. 'class.config.php');
require_once(MTTINC. 'notifications.php');

configureDbConnection();

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
set_nocache_headers();

if (!defined('MTT_DISABLE_EXT')) {
    define('MTT_EXT', MTTPATH . 'ext/');
    loadExtensions();
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

function configureDbConnection()
{

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

    # Check tables created
    global $checkDbExists;
    if (!Config::$noDatabase && isset($checkDbExists) && $checkDbExists) {
        $exists = $db->tableExists($db->prefix.'settings');
        if (!$exists) {
            die("Need to create or update the database. Run <a href=setup.php>setup.php</a> first.");
        }
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
    if ( need_auth() ) {
        if (!isset($_SESSION)) return '';
        return $_SESSION['token'] ?? '';
    }
    else {
        if (!isset($_COOKIE)) return '';
        return $_COOKIE['mtt-token'] ?? '';
    }
}

function check_token()
{
    $token = access_token();
    if ($token == '' || !isset($_SERVER['HTTP_MTT_TOKEN']) || $_SERVER['HTTP_MTT_TOKEN'] != $token) {
        http_response_code(403);
        die("Access denied! No token provided.");
    }
}

function update_token(): string
{
    $token = generateUUID();
    if ( need_auth() ) {
        $_SESSION['token'] = $token;
    }
    else {
        if (PHP_VERSION_ID < 70300) {
            setcookie('mtt-token', $token, 0, url_dir(get_unsafe_mttinfo('mtt_url')). '; samesite=lax', '', false, true );
        }
        else {
            setcookie('mtt-token', $token, [
                'path' => url_dir(get_unsafe_mttinfo('mtt_url')),
                'httponly' => true,
                'samesite' => 'lax'
            ]);
        }
        $_COOKIE['mtt-token'] = $token;
    }
    return $token;
}

function setup_and_start_session()
{
    require_once(MTTINC. 'class.sessionhandler.php');
    session_set_save_handler(new MTTSessionHandler());

    ini_set('session.use_cookies', true);
    ini_set('session.use_only_cookies', true);

    /*
        After any request we may have 14 days of inactivity (i.e. not requesting session data),
        then we have to re-login (look at MTTSessionHandler).
        Activity without re-login lasts for max 60 days, the cookie lifetime, then cookie dies
        and we have to re-login having new session id.
    */

    $lifetime = 5184000; # 60 days session cookie lifetime
    $path = url_dir(Config::get('url')=='' ? getRequestUri() : Config::getUrl('url'));

    if (PHP_VERSION_ID < 70300) {
        # this is a known samesite flag workaround, was fixed in 7.3
        session_set_cookie_params($lifetime, $path. '; samesite=lax', null, null, true);
    } else {
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => $path,
            'httponly' => true,
            'samesite' => 'lax'
        ]);
    }
    session_name('mtt-session');
    session_start();
}

function timestampToDatetime($timestamp, $forceTime = false) : string
{
    $format = Config::get('dateformat');
    if ($forceTime || Config::get('showtime')) {
        $format .= ' '. (Config::get('clock') == 12 ? 'g:i A' : 'H:i');
    }
    return formatTime($format, $timestamp);
}

function formatTime($format, $timestamp=0) : string
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

function _e(string $s)
{
    echo __($s, true);
}

function __(string $s, bool $escape = false)
{
    $v = Lang::instance()->get($s);
    return $escape ? htmlspecialchars($v) : $v;
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
        case 'theme_url':
            $_mttinfo['theme_url'] = get_unsafe_mttinfo('mtt_uri'). 'content/'. MTT_THEME. '/';
            return $_mttinfo['theme_url'];
        case 'content_url':
            $_mttinfo['content_url'] = get_unsafe_mttinfo('mtt_uri'). 'content/';
            return $_mttinfo['content_url'];
        case 'url':
            /* full url to homepage: directory with root index.php or custom index file in the root. */
            /* ex: http://my.site/mytinytodo/   or  https://my.site/mytinytodo/home_for_2nd_theme.php  */
            /* Should not contain a query string. Have to be set in config if custom port is used or wrong detection. */
            $_mttinfo['url'] = Config::getUrl('url');
            if ($_mttinfo['url'] == '') {
                $is_https = is_https();
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
        case 'mtt_uri':
            $_mttinfo['mtt_uri'] = Config::getUrl('mtt_url'); // need to have a trailing slash
            if ($_mttinfo['mtt_uri'] == '') {
                if ( ''  !=  $url = Config::getUrl('url') ) {
                    $_mttinfo['mtt_uri'] = url_dir($url);
                }
                else {
                    $_mttinfo['mtt_uri'] = url_dir(getRequestUri());
                }
            }
            return $_mttinfo['mtt_uri'];
        case 'api_url':
            /* URL for API, like http://localhost/mytinytodo/api/. No need to set by default. */
            $_mttinfo['api_url'] = Config::getUrl('api_url'); // need to have a trailing slash
            if ($_mttinfo['api_url'] == '') {
                $_mttinfo['api_url'] = get_unsafe_mttinfo('mtt_uri'). 'api.php?_path=/';
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

function is_https(): bool
{
    if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
        return true;
    }
    if (defined('MTT_USE_HTTPS') && MTT_USE_HTTPS) {
        return true;
    }
    // This HTTP header can be overriden by user agent!
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
        return true;
    }
    return false;
}

function set_nocache_headers()
{
    // little more info at https://www.php.net/manual/en/function.session-cache-limiter.php
    header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    header('Expires: Wed, 29 Apr 2009 10:00:00 GMT');
    header('Pragma: no-cache'); // for old HTTP/1.0 intermediate caches
}

function jsonExit($data)
{
    header('Content-type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    MTTNotificationCenter::postDidFinishRequestNotification();
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

function loadExtensions()
{
    $a = Config::get('extensions');
    if (!$a || !is_array($a)) {
        return;
    }
    foreach ($a as $ext) {
        if (is_string($ext)) {
            try {
                MTTExtensionLoader::loadExtension($ext);
            }
            catch (Exception $e) {
                error_log($e->getMessage());
            }

        }
    }
}
