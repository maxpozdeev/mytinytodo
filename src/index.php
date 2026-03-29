<?php
/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2010,2020-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/


$checkDbExists = true; #TODO: only index page?
require_once('./init.php');

if ( access_token() == '' ) {
    update_token();
}

$path = getIndexPath();

if ($path === '/') {
    // if (is_logged()) {
    //     # redirect to /u/<username> ?
    //     redirectExit(get_user_router_url(''));
    // }
    page_tasks();
}
else if ($path === '/go' ) {
    handleGoRoute($_SERVER['QUERY_STRING'] ?? '');
}
else if ($path === '/login') {
    if (is_logged()) {
        redirectExit( get_user_router_url() );
    }
    page_login();
}
else if (preg_match("#^/u/([^/]+)(.*)#", $path, $m)) {
    handleUser($m[1], $m[2]);
}
else {
    page_404();
}

MTTNotificationCenter::postDidFinishRequestNotification();

exit;

/*
$endpoints = array(
    '/u/([^/]+)' => [
        'GET' => [] # User tasks
    ]
);

foreach ($endpoints as $search => $methods) {
}
*/


// end


function getIndexPath(): string
{
    if (!defined('MTT_USE_REWRITE') || !MTT_USE_REWRITE) {
        if (isset($_GET['_path'])) {
            return $_GET['_path'];
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
    unset($q['_path']);

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
    $homeUrl = htmlspecialchars(Config::getUrl('url'));
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
        "goPrefix" => htmlspecialchars(get_go_prefix()),
        "routerPrefix" => htmlspecialchars(get_router_url('')),
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

    MTTVars::$requestedUserId = (int) (new UserRepo(DBConnection::instance()))->findUserIdByUsername(MTTVars::$requestedUsername);
    if (!MTTVars::$requestedUserId)
        return page_404();

    page_tasks();
}

function page_404()
{
    http_response_code(404);
    print "<h1>Page Not Found</h1>";
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

class MTTVars {
    static string $requestedUsername = '';
    static int $requestedUserId = 0;
}

