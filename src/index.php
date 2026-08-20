<?php
/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2010,2020-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/


$checkDbExists = true; #TODO: only index page?
require_once('./init.php');

if (MTTVars::$isStateless) {
    page_403();
    exit;
}

if ( access_token() == '' ) {
    update_token();
}

parseRoute( getIndexPath() );

MTTNotificationCenter::postDidFinishRequestNotification();

exit;


function parseRoute(string $path)
{
    if ($path === '/') {
        // if (is_logged()) {
        //     # redirect to /@<username> ?
        //     redirectExit(get_user_router_url(''));
        // }
        if (!is_logged()) {
            redirectExit(routerMakeUrl('login', ['ret'=>'home']));
        }
        page_tasks();
    }
    else if ($path === '/login') {
        if (is_logged()) {
            redirectExit( routerMakeUserUrl() );
        }
        page_login();
    }
    else if ($path === '/go' ) {
        handleGoRoute($_SERVER['QUERY_STRING'] ?? '');
    }
    else if ($path === '/reset') {
        page_reset();
    }
    else if ($path === '/new-password') {
        page_new_password();
    }
    else if (preg_match("#^/@([^/]+)(.*)#", $path, $m)) {
        handleUser($m[1], $m[2]);
    }
    else if (preg_match("#^/settings/([^/]+)$#", $path, $m)) {
        handleUserSettings($m[1]);
    }
    else if (preg_match("#^/controlpanel/([^/]+)$#", $path, $m)) {
        handleControlPanel($m[1]);
    }
    else {
        page_404();
    }
}

function getIndexPath(): string
{
    if (!MTT_USE_REWRITE) {
        if (isset($_GET['p'])) {
            $path = $_GET['p'];
            if ($path == '' || $path[0] != '/')
                return '/'. $path;
            return $path;
        }
        else if ('' !== ($_SERVER['QUERY_STRING'] ?? '')) {
            return '/go'; #hack
        }
        return '/';
    }
    $path = $_SERVER['REQUEST_URI'] ?? '';
    if (false !== $p = strpos($path, '?')) {
        $path = substr($path, 0, $p);
    }
    $uri = get_unsafe_mttinfo('uri');
    if ($path != '' && 0 === strncmp($path, $uri, strlen($uri))) {
        $path = substr($path, strlen($uri) -1);
    }
    return $path;
}

function handleGoRoute(?string $queryString = null)
{
    if ($queryString === null)
        $queryString = $_SERVER['QUERY_STRING'] ?? '';

    if ($queryString == '')
        redirectExit(get_unsafe_mttinfo('url'));

    parse_str($queryString, $q);
    unset($q['p']);

/*
    if (isset($q['user'])) {
        $q['user'] = trim($q['user']);
        if ($q['user'] == '') {
            htmlExit(404, "Page not found");
        }
        $userId = (int) (new UserRepo(DBConnection::instance()))->findUserIdByUsername($q['user']);
        if (!$userId) {
            htmlExit(404, "User not found");
        }
    }
    else {
        # No user specified
        if (is_logged()) {
            // User dashboard
        }
        else {
            // Guest main page
            // Request for login?
        }
    }
*/

    if (isset($q['list'])) {
        $hash = ($q['list'] == 'alltasks') ? ['alltasks'] : ['list', (int)$q['list']];
        unset($q['list']);
        if (isset($q['tags'])) {
            $hash[] = 'tags';
            $hash[] = (string)$q['tags'];
            unset($q['tags']);
        }
        if (isset($q['search'])) {
            $hash[] = 'search';
            $hash[] = (string)$q['search'];
            unset($q['search']);
        }
        redirectWithHashRoute($hash, $q);
    }
    else if (isset($q['task'])) {
        // TODO: check access
        $taskRepo = new TaskRepo(DBConnection::instance());
        $listId = $taskRepo->findListIdByTaskId((int)$q['task']);
        if ($listId > 0) {
            $h = [ 'list', $listId, 'search', '#'. (int)$q['task']];
            redirectWithHashRoute($h);
        }
        htmlExit(404, "Task not found");
    }
}

function redirectWithHashRoute(array $hash, array $q = [])
{
    $url = get_unsafe_mttinfo('url');
    $query = http_build_query($q);
    if ($query != '') $url .= "?$query";
    if (count($hash) > 0) {
        $encodedHash = implode("/", array_map("rawurlencode", $hash));
        $url .= "#$encodedHash";
    }
    redirectExit($url);
}

function js_options()
{
    // Here we can use URIs instead of full URLs.
    $homeUrl = htmlspecialchars(Config::getUrl('url') ?? '');
    if ($homeUrl == '') {
        $homeUrl = get_mttinfo('mtt_uri');
    }
    $a = array(
        "token" => htmlspecialchars(access_token()),
        "me" => username() ?? '',
        "username" => MTTVars::$requestedUsername,
        "title" => get_unsafe_mttinfo('title'),
        "mttUrl" => get_mttinfo('mtt_uri'),
        "homeUrl" => $homeUrl,
        "apiUrl" => get_mttinfo('api_url'),
        "goPrefix" => htmlspecialchars(routerGetGoPrefix()),
        "routerPrefix" => htmlspecialchars(routerMakeUrl('')),
        "tasksUrl" => get_mttinfo('tasks_uri'),
        "needAuth" => need_auth() ? true : false,
        "isLogged" => is_logged() ? true : false,
        "showdate" => Config::get('showdate') ? true : false,
        "showtime" => Config::get('showtime') ? true : false,
        "showdateInline" => Config::get('showdateInline') ? true : false,
        "duedatepickerformat" => htmlspecialchars(Config::get('dateformat2')),
        "firstdayofweek" => (int) Config::get('firstdayofweek'),
        "calendarIcon" => get_mttinfo('theme_url'). 'images/calendar.svg',
        "autotag" => Config::get('autotag') ? true : false,
        "markdown" => Config::get('markup') == 'v1' ? false : true,
        "newTaskCounter" => Config::get('newTaskCounter') ? true : false,
        "newTaskCounterIcon" => Config::get('newTaskCounterIcon') ? true : false,
        "lang" => Lang::instance()->jsStrings(),
    );
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
    if (MTT_DEBUG) {
        $flags |= JSON_PRETTY_PRINT;
    }
    $json = json_encode($a, $flags);
    if ($json === false) {
        error_log("MTT Error: Failed to encode array of options to JSON. Code: ". (int)json_last_error());
        echo "{}";
    }
    else {
        echo $json;
    }
}

function htmlExit(int $code = 200, string $msg = '')
{
    if ($msg != '') {
        print($msg);
    }
    else {
        print "Status $code\n";
    }
    http_response_code($code);
    exit;
}


function handleUser(string $username, string $path = '')
{
    MTTVars::$requestedUsername = trim($username);
    if (MTTVars::$requestedUsername === '')
        return page_404();

    if (isset(MTTVars::$username) && MTTVars::$requestedUsername === MTTVars::$username) {
        MTTVars::$requestedUserId = userId();
    }
    else {
        MTTVars::$requestedUserId = (int) (new UserRepo(DBConnection::instance()))->findUserIdByUsername(MTTVars::$requestedUsername);
    }

    if (!MTTVars::$requestedUserId)
        return page_404();

    if (!need_auth() && MTTVars::$requestedUserId !== userId())
        return page_404();

    page_tasks();
}

function page_404()
{
    http_response_code(404);
    print "<h1>Page Not Found</h1>";
}

function page_403()
{
    http_response_code(403);
    print "<h1>Forbidden</h1>";
}

function page_500()
{
    http_response_code(500);
    print "<h1>Error. See details in logs.</h1>";
}

function page_login()
{
    require_once(MTT_THEME_PATH. 'header.php');
    require_once(MTT_THEME_PATH. 'login.php');
    require_once(MTT_THEME_PATH. 'footer.php');
}

function page_tasks()
{
    require_once(MTT_THEME_PATH. 'header.php');
    require_once(MTT_THEME_PATH. 'tasks.php');
    require_once(MTT_THEME_PATH. 'footer.php');
}

function page_reset()
{
    require_once(MTT_THEME_PATH. 'header.php');
    require_once(MTT_THEME_PATH. 'reset.php');
    require_once(MTT_THEME_PATH. 'footer.php');
}

function page_new_password()
{
    require_once(MTT_THEME_PATH. 'header.php');
    require_once(MTT_THEME_PATH. 'new-password.php');
    require_once(MTT_THEME_PATH. 'footer.php');
}


function mtt_settings_page_url()
{
    echo get_mtturl(MTTVars::$settingsPage);
}

function mtt_get_settings_page_url(): string
{
    return get_mtturl(MTTVars::$settingsPage);
}

function handleControlPanel(string $page)
{
    if (!is_logged()) {
        return page_403();
    }
    if (!is_admin()) {
        return page_403();
    }

    static $pages = [
        'general' => 'general.php',
        'extensions' => 'extensions.php',
        'ext-settings' => 'ext-settings.php',
        'css' => 'css.php',
    ];
    if (!isset($pages[$page])) {
        return page_404();
    }
    MTTVars::$settingsPage = 'controlpanel/'. $page;
    MTTVars::$settingsPageFile = MTTINC. 'settings/'. $pages[$page];
    define('MTT_PAGE', MTTVars::$settingsPageFile);
    require_once(MTTINC. 'settings/procs.php');
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        require_once(MTTVars::$settingsPageFile);
        exit();
    }
    require_once(MTT_THEME_PATH. 'header.php');
    require_once(MTTINC. 'settings/controlpanel.php');
    require_once(MTT_THEME_PATH. 'footer.php');
}

function handleUserSettings(string $page)
{
    if (!is_logged()) {
        return page_403();
    }
    static $pages = [
        'general' => 'user-general.php',
        'account' => 'user-account.php',
    ];
    if (!isset($pages[$page])) {
        return page_404();
    }
    MTTVars::$settingsPage = 'settings/'. $page;
    MTTVars::$settingsPageFile = MTTINC. 'settings/'. $pages[$page];
    define('MTT_PAGE', MTTVars::$settingsPageFile);
    require_once(MTTINC. 'settings/procs.php');
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        check_token();
        require_once(MTTVars::$settingsPageFile);
        exit();
    }
    require_once(MTT_THEME_PATH. 'header.php');
    require_once(MTTINC. 'settings/user-settings.php');
    require_once(MTT_THEME_PATH. 'footer.php');
}


function isLoggedUserArea() : bool
{
    if (!is_logged())
        return false;
    if (MTTVars::$requestedUserId == 0)
        return true;
    if (MTTVars::$requestedUserId == userId())
        return true;
    return false;
}
