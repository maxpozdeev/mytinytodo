<?php
/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2011,2019-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (PHP_VERSION_ID < 70400) {
    die("PHP 7.4 or above is required");
}


if(!defined('MTTPATH')) define('MTTPATH', dirname(__FILE__) .'/');
if(!defined('MTTINC'))  define('MTTINC', MTTPATH. 'includes/');
if(!defined('MTT_CONTENT_PATH')) define('MTT_CONTENT_PATH', MTTPATH. 'content/');

requireConfig();

if (!defined('MTT_THEME')) {
    define('MTT_THEME', 'theme');
    define('MTT_THEME_PATH', MTTINC. 'theme/');
}
else {
    define('MTT_THEME_PATH', MTT_CONTENT_PATH. MTT_THEME. '/');
}

if (!defined('MTT_USE_REWRITE')) {
    define('MTT_USE_REWRITE', false);
}

if (getenv('MTT_ENABLE_DEBUG') == 'YES' || (defined('MTT_DEBUG') && MTT_DEBUG) ) {
    if (!defined('MTT_DEBUG')) define('MTT_DEBUG', true);
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
}
else {
    //ini_set('display_errors', '0');
    //ini_set('log_errors', '1');
    if (!defined('MTT_DEBUG')) define('MTT_DEBUG', false);
}

if (!defined('MTT_MULTIUSER')) {
    define('MTT_MULTIUSER', 1);
}

require_once(MTTINC. 'vars.php');
require_once(MTTINC. 'common.php');
require_once(MTTINC. 'classes.php');
require_once(MTTINC. 'class.dbconnection.php');
require_once(MTTINC. 'class.config.php');
require_once(MTTINC. 'notifications.php');
require_once(MTTINC. 'filters.php');
require_once(MTTINC. 'markup.php');
require_once(MTTINC. 'entities.php');
require_once(MTTINC. 'repository.list.php');
require_once(MTTINC. 'repository.tag.php');
require_once(MTTINC. 'repository.task.php');
require_once(MTTINC. 'repository.user.php');

configureDbConnection();

Config::loadAppConfig();

date_default_timezone_set(Config::get('timezone'));

if (need_auth() && !isset($dontStartSession) && !Config::$noDatabase) {
    if ( !isset(MTTVars::$isStateless) && isset($_SERVER['HTTP_AUTHORIZATION']) ) {
        MTTVars::$isStateless = true;
        checkBasicAuth();
    }
    else {
        MTTVars::$isStateless = false;
        setup_and_start_session();
    }
}
if (!isset($dontStartSession)) {
    Config::loadUserConfig();
}
set_nocache_headers();


require_once(MTTINC. 'class.lang.php');
//User can override language setting by cookies or query
if (isset($_COOKIE['lang']) && preg_match("/^[a-z-]+$/i", $_COOKIE['lang'])) {
    if (Lang::langExists($_COOKIE['lang']))
        MTTVars::$forcedLang = $_COOKIE['lang'];
}

Lang::loadLang( MTTVars::$forcedLang ?: Config::get('lang') );
if (Lang::instance()->rtl()) {
    MTTVars::$isRtl = true;
}

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
            $db = new MysqliDatabase();
        }
        else {
            require_once(MTTINC. 'class.db.mysql.php');
            $db = new MysqlDatabase();
        }
        DBConnection::init($db);
        try {
            $db->connect([
                'host' => MTT_DB_HOST,
                'user' => MTT_DB_USER,
                'password' => MTT_DB_PASSWORD,
                'db' => MTT_DB_NAME,
            ]);
        }
        catch(Exception $e) {
            logAndDie("Failed to connect to mysql database: ". $e->getMessage());
        }
        $db->dq("SET NAMES utf8mb4");
    }

    # PostgreSQL Database
    else if (MTT_DB_TYPE == 'postgres')
    {
        require_once(MTTINC. 'class.db.postgres.php');
        $db = DBConnection::init(new PostgresDatabase());
        try {
            $db->connect([
                'host' => MTT_DB_HOST,
                'user' => MTT_DB_USER,
                'password' => MTT_DB_PASSWORD,
                'db' => MTT_DB_NAME,
            ]);
        }
        catch(Exception $e) {
            $errlog = "Failed to connect to PostgreSQL database: ". $e->getMessage();
            if (MTT_DEBUG) {
                logAndDie($errlog);
            }
            else {
                logAndDie("Failed to connect to database", $errlog);
            }
        }
        $db->dq("SET NAMES 'utf8'");
    }

    # SQLite3 Database
    elseif (MTT_DB_TYPE == 'sqlite')
    {
        require_once(MTTINC. 'vendor/autoload.php');
        require_once(MTTINC. 'class.db.sqlite.php');
        $params = null;
        if (defined('MTT_SQLITE_NORMALIZE_SEARCH')) {
            $params = [ 'useNormalizedSearch' => boolval(MTT_SQLITE_NORMALIZE_SEARCH) ];
        }
        $db = DBConnection::init(new SqliteDatabase($params));
        $db->connect([
            'filename' => MTTPATH. 'db/todolist.db'
        ]);
    }
    else {
        die("Incorrect database connection config");
    }

    DBConnection::setTablePrefix(MTT_DB_PREFIX);

    if (MTT_DEBUG && defined('MTT_DEBUG_QUERY_FILE')) {
        if (!$db->setLogQueryToFile(MTT_DEBUG_QUERY_FILE)) {
            error_log("MTT_DEBUG_QUERY_FILE is not writable - ". MTT_DEBUG_QUERY_FILE);
        }
    }

    # Check tables created
    global $checkDbExists;
    if (!Config::$noDatabase && isset($checkDbExists) && $checkDbExists) {
        $exists = $db->tableExists($db->prefix.'users');
        if (!$exists) {
            die("Need to create or update the database. Run <a href=setup.php>setup.php</a> first.");
        }
    }
}

// return false if mtt is configured to work as old version
// - only one user
// - no password
// - no sessions are used
function need_auth(): bool
{
    return MTT_MULTIUSER ? true : false;
}

function is_logged(bool $validateSignature = true): bool
{
    if ( !need_auth() )
        return true;

    if ( !isset(MTTVars::$isStateless) )
        return false;

    if (MTTVars::$isStateless) {
        if (MTTVars::$userId) {
            return true;
        }
        return false;
    }

    if (session_status() !== PHP_SESSION_ACTIVE)
        return false;
    if ( !isset($_SESSION['sign'])  ||  !isset($_SESSION['userId']) )
        return false;

    if ( !(int)$_SESSION['userId'] )
        return false;

    if ($validateSignature) {
        // actual validation is in loadUserConfig()
        if (isset(MTTVars::$isSessionInvalid))
            return false;
    }

    return true;
}

/**
 * Get id of authenticated user in current session
 * Returns null if not authenticated
 * Does not return 0
 * @return null|int
 */
function userId(bool $validateSignature = true): ?int
{
    if (!need_auth())
        return 1;
    if (!is_logged($validateSignature))
        return null;
    $userId = MTTVars::$isStateless ? MTTVars::$userId : (int)$_SESSION['userId'];
    if ($userId <= 0)
        throw new Exception("Unexpected user id (0)");
    return $userId;
}

function username(): ?string
{
    if (!need_auth())
        return 'admin';
     if (!is_logged())
        return null;
    return MTTVars::$username ?? null;
}

function is_admin(): bool
{
    return (userId() === 1);
}

function updateSessionLogged( bool $logged,
    #[\SensitiveParameter]
    ?array $user = null )
{
    if ($logged) {
        if (is_null($user) || !isset($user['id']) || $user['id'] == 0 || !isset($user['username'])) {
            throw new Exception("Unexpected user data");
        }
        $_SESSION['userId'] = (int)$user['id'];
        MTTVars::$username = $user['username'];
        MTTVars::$userPwToken = $user['pwtoken'];
        $_SESSION['sign'] = sessionSignature($user['pwtoken']);
    }
    else {
        unset($_SESSION['userId']);
        unset($_SESSION['sign']);
    }
    // remove unused session vars since 2.0
    unset($_SESSION['logged']);
    unset($_SESSION['username']);
}

function sessionSignature(
    #[SensitiveParameter]
    string $pwtoken
): string
{
    return idSignature(session_id(), $pwtoken, defined('MTT_SALT') ? MTT_SALT : '');
}

function access_token(): string
{
    if ( need_auth() ) {
        return $_SESSION['token'] ?? '';
    }
    else {
        return $_COOKIE['mtt-token'] ?? '';
    }
}

/**
 * Check if HTTP request have required MTT-Token header with value
 * the same as stored in session (if password set) or mtt-token cookie (if no password).
 * Prohibits further execution if no tokens are found.
 * @return void
 */
function check_token()
{
    if (MTTVars::$isStateless) {
        if (MTTVars::$userId) {
            return;
        }
        http_response_code(500);
        die("Access denied! Unexpected stateless data.\n");
    }
    $token = access_token();
    if ($token == '' || !isset($_SERVER['HTTP_MTT_TOKEN']) || $_SERVER['HTTP_MTT_TOKEN'] !== $token) {
        http_response_code(403);
        die("Access denied! Authentication is required.\n");
    }
}

function update_token(): string
{
    $token = generateUUID();
    if ( need_auth() ) {
        $_SESSION['token'] = $token;
        if (isset($_COOKIE['mtt-token'])) {
             //clear mtt-token cookie
             setcookie('mtt-token', '', [
                'path' => url_dir(get_unsafe_mttinfo('mtt_url')),
                'httponly' => true,
                'samesite' => 'lax',
                'expires' => time() - 3600,
            ]);
        }
    }
    else {
        setcookie('mtt-token', $token, [
            'path' => url_dir(get_unsafe_mttinfo('mtt_url')),
            'httponly' => true,
            'samesite' => 'lax'
        ]);
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
    ini_set('session.use_strict_mode', false);
    ini_set('session.lazy_write', true);

    /*
        After any request we may have 14 days of inactivity (i.e. not requesting session data),
        then we have to re-login (look at MTTSessionHandler).
        Activity without re-login lasts for max 60 days, the cookie lifetime, then cookie dies
        and we have to re-login having new session id.
    */

    $lifetime = 5184000; # 60 days session cookie lifetime
    $path = url_dir(Config::get('url')=='' ? getRequestUri() : Config::getUrl('url'));

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => $path,
        'httponly' => true,
        'samesite' => 'lax'
    ]);
    session_name('mtt-session');
    session_start();
}


function userDataByBasicAuth(): ?array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (stripos($header, 'basic') !== 0) {
        return null;
    }
    $base64 = substr($header, 6) ?: '';
    $payload = base64_decode($base64) ?: '';
    if (strpos($payload, ':') === false) {
        return null;
    }
    list($username, $password) = explode(':', $payload, 2);

    $repo = new UserRepo(DBConnection::instance());
    $userdata = $repo->userDataByUsername($username);
    if (!$userdata) {
        return null;
    }
    $extra = json_decode($userdata['extra'] ?? '', true);
    if (!$extra) {
        return null;
    }
    if (!isset($extra['apppasswords']) || !is_array($extra['apppasswords'])) {
        return null;
    }
    foreach ($extra['apppasswords'] as $row) {
        if (isPasswordEqualsToHash($password, $row['hash'] ?? '')) {
            return $userdata;
        }
    }
    return null;
}

function checkBasicAuth()
{
    $data = userDataByBasicAuth();
    if (!$data) {
        http_response_code(401);
        die("Authorization required\n");
    }
    MTTVars::$user = $data['name'];
    MTTVars::$username = $data['username'];
    MTTVars::$userId = (int)$data['id'];
    MTTVars::$userPwToken = $data['pwtoken'];
}

function timestampToDatetime(int $timestamp, bool $forceTime = false) : string
{
    $format = Config::get('dateformat');
    if ($forceTime || Config::get('showtime')) {
        $format .= ' '. (Config::get('clock') == 12 ? 'g:i A' : 'H:i');
    }
    return formatTime($format, $timestamp);
}

function formatTime(string $format, int $timestamp = 0) : string
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

function __(string $s, bool $escape = false, ?string $arg = null)
{
    $v = Lang::instance()->get($s);
    if (null !== $arg) {
        $v = sprintf($v, $arg);
    }
    return $escape ? htmlspecialchars($v) : $v;
}

function mttinfo(string $v)
{
    echo get_mttinfo($v);
}

function get_mttinfo(string $v)
{
    return htmlspecialchars( get_unsafe_mttinfo($v) );
}

/*
 * Returned values from get_unsafe_mttinfo() can be unsafe for html.
 * But '\r' and '\n' in URLs taken from config are removed.
 */
function get_unsafe_mttinfo(string $v)
{
    $info = &MTTVars::$info;

    if (isset($info[$v])) {
        return $info[$v];
    }
    switch($v)
    {
        case 'theme_url':
            $info['theme_url'] = get_unsafe_mttinfo('mtt_url'). 'content/'. MTT_THEME. '/';
            return $info['theme_url'];
        case 'content_url':
            $info['content_url'] = get_unsafe_mttinfo('mtt_url'). 'content/';
            return $info['content_url'];
        case 'url':
            # Full url to homepage: directory (!) with root index.php.
            # Prefix for pretty links. Used for links to lists or exports.
            # ex: http://my.site/  or  http://my.site/mytinytodo/
            # Should not contain a query string. Have to be set in config if custom port is used or wrong detection.
            $info['url'] = Config::getUrl('url');
            if ($info['url'] == '') {
                $is_https = is_https();
                # server port is a part of HTTP_HOST
                $info['url'] = ($is_https ? 'https://' : 'http://'). $_SERVER['HTTP_HOST']. url_dir(getRequestUri());
            }
            if ($info['url'] == '' || $info['url'][-1] != '/')
                $info['url'] .= '/';
            return $info['url'];
        case 'uri':
            # URI part of script url (without a protocol://hostname:port part).
            # By default is the same as mtt_uri. e.g. / or /mtt/
            $info['uri'] = url_dir( get_unsafe_mttinfo('url') );
            return $info['uri'];
        case 'mtt_url':
            # Full url to script installation: directory with root api.php.
            # Used internally for api requests and assets loading.
            # No need to set if you use default directory structure. By default it's the same as 'url'.
            $info['mtt_url'] = Config::getUrl('mtt_url'); // need to have a trailing slash
            if ($info['mtt_url'] == '') {
                $info['mtt_url'] = url_dir( get_unsafe_mttinfo('url'), false );
            }
            return $info['mtt_url'];
        case 'mtt_uri':
            # Same as mtt_url but URI only, without a protocol://hostname:port part
            $url = get_unsafe_mttinfo('mtt_url');
            $info['mtt_uri'] = parse_url($url, PHP_URL_PATH);
            return $info['mtt_uri'];
        case 'title':
            $info['title'] = (Config::get('title') != '') ? Config::get('title') : __('My Tiny Todolist');
            return $info['title'];
        case 'version':
            $info['version'] = MTTVersion::VERSION;
            return $info['version'];
        case 'appearance':
            $info['appearance'] = Config::get('appearance');
            return $info['appearance'];
        case 'username':
            $info['username'] = username() ?? '';
            return $info['username'];
        case 'user':
            $info['user'] = MTTVars::$user ?? '';
            return $info['user'];
        case 'tasks_uri':
            if (need_auth())
                $info['tasks_uri'] = routerMakeUserUrl();
            else
                $info['tasks_uri'] = get_unsafe_mttinfo('uri');
            return $info['tasks_uri'];
        default:
            error_log("Unknown mttinfo key: $v");
            return '';
    }
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

function jsonExit(array $data)
{
    (new JsonApiResponse($data))->exit();
}

function redirectExit(string $url)
{
    $url = str_replace(["\r","\n"], "", $url);
    header("Location: ". $url, true, 302);
    exit;
}

function routerMakeUrl(string $path = '', ?array $qsa = null, bool $fullUrl = false): string
{
    $prefix = $fullUrl ? get_unsafe_mttinfo('url') : get_unsafe_mttinfo('uri');
    if (!MTT_USE_REWRITE) {
        return $prefix. '?p='. $path. ($qsa !== null ? '&'. http_build_query($qsa) : '');
    }
    else {
        return $prefix. $path. ($qsa !== null ? '?'. http_build_query($qsa) : '');
    }
}

function apiMakeUrl(string $path = '', ?array $qsa = null, bool $fullUrl = false): string
{
    $prefix = $fullUrl ? get_unsafe_mttinfo('mtt_url') : get_unsafe_mttinfo('mtt_uri');
    if (!MTT_USE_REWRITE) {
        return $prefix. 'api.php?_path='. $path. ($qsa !== null ? '&'. http_build_query($qsa) : '');
    }
    else {
        return $prefix. 'api/'. $path. ($qsa !== null ? '?'. http_build_query($qsa) : '');
    }
}

function mtturl(string $path)
{
    echo get_mtturl($path);
}

function get_mtturl(string $path, ?array $qsa = null): string
{
    return htmlspecialchars(routerMakeUrl($path, $qsa));
}

function routerMakeUserUrl(string $path = '', string $user = ''): string
{
    $prefix = routerMakeUrl('');
    if ($path !== '' && $path[0] !== '/')
        $path = '/'. $path;
    if ($user == '')
        $user = username();
    return $prefix . '@'. $user. $path;
}

function routerGetGoPrefix()
{
    $prefix = get_unsafe_mttinfo('uri');
    if (!MTT_USE_REWRITE) {
        return $prefix. '?';
    }
    else {
        return $prefix .= 'go?';
    }
}

function logAndDie(string $userText, ?string $errText = null)
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
    $a = Config::getList('extensions');
    if (!$a)
        return;
    foreach ($a as $ext) {
        if (is_string($ext)) {
            try {
                MTTExtensionLoader::loadExtension($ext);
            }
            catch (MTTExtensionLoaderException $e) {
                error_log($e->getMessage());
            }
            catch (Exception $e) {
                if (MTT_DEBUG)
                    throw $e;
                else
                    error_log("Error while loading extension '$ext': ". $e->getMessage());
            }

        }
    }
}

function get_filever(string $dir, string $filename, ?string $ext = null)
{
    if (!MTT_DEBUG) {
        return get_mttinfo('version');
    }
    $prefix = get_mttinfo('version'). '-'. time();
    $path = null;
    if ($dir == 'content') {
        $path = MTTPATH. 'content/';
    }
    else if ($dir == 'theme') {
        $path = MTTPATH. 'content/'. MTT_THEME. '/';
    }
    else if ($dir == 'ext') {
        $path = MTT_EXT. $ext. '/';
    }
    else {
        return $prefix. '-unknown';
    }
    $fullPath = $path. $filename;
    if (!file_exists($fullPath)) {
        return $prefix. '-not-found';
    }
    $mtime = filemtime($fullPath);
    if ($mtime === false) {
        return $prefix. '-no-access';
    }
    return $mtime;
}

function filever(string $dir, string $filename)
{
    print get_filever($dir, $filename);
}

function canReadList(TaskList $list, string $inFeedKey = '') : bool
{
    if (!need_auth() && userId(false) !== $list->userId)
        return false;

    if ($list->isPublished)
        return true;

    if (is_logged() && userId() === $list->userId)
        return true;

    $feedKey = (string) ($list->extra['feedKey'] ?? '');
    if ($feedKey !== '' && $feedKey === $inFeedKey)     //check length?
        return true;

    return false;
}


function canWriteToList(AbstractTaskList $list) : bool
{
    return (is_logged() && userId() === $list->userId);
}


function suggestedMailFrom(): string
{
    $host = parse_url(get_unsafe_mttinfo('url'), PHP_URL_HOST);
    $host = preg_replace('/^(www\.)/', '', $host);
    if (function_exists('posix_getpwuid') && false !== ($userinfo = posix_getpwuid(posix_getuid())) ) {
        return $userinfo['name']. '@'. $host;
    }
    return "mytinytodo@$host";
}


function mtt_mail(string $to, string $subject, string $message)
{
    require_once(MTTINC. 'vendor/phpmailer/phpmailer/src/PHPMailer.php');

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {

        // $mail->isSMTP();                                      // Set mailer to use SMTP
        // $mail->Host       = 'smtp.example.com';               // Specify main and backup SMTP servers
        // $mail->SMTPAuth   = true;                             // Enable SMTP authentication
        // $mail->Username   = 'your_email@example.com';         // SMTP username
        // $mail->Password   = 'your_password';                  // SMTP password
        // $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;   // Enable TLS encryption (or ENCRYPTION_SMTPS for SSL)
        // $mail->Port       = 587; //587 for STARTTLS, 465 - for SSL (SMTPS)

        $mail->XMailer = "myTinyTodo Mailer";
        $mail->CharSet = "UTF-8";
        $mail->setFrom(suggestedMailFrom());
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->send();
    }
    catch (Exception $e) {
        MTTVars::$mailerLastError = $e->getMessage();
        error_log("PHPMailer exception: ". $e->getMessage());
        return false;
    }
    MTTVars::$mailerLastError = '';
    return true;
}
