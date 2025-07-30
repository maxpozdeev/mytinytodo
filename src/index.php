<?php
/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2010,2020-2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/


$checkDbExists = true;
require_once('./init.php');

if ( access_token() == '' ) {
    update_token();
}

$path = getIndexPath();

if ($path === '/') {
    page_tasks();
}
else if ($path === '/go' ) {
    if ( isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '' ) {
        parseGoRoute($_SERVER['QUERY_STRING']);
    }
    else {
        header("Location: ". get_unsafe_mttinfo('url'));
        exit;
    }
}
else if ($path == '/u/') {
}
else if ($path === '/login') {
    if (is_logged()) {
        header("Location: ". get_unsafe_mttinfo('url'));
        exit;
    }
    page_login();
}
else {
    http_response_code(404);
    print "<h1>Page Not Found</h1>";
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

function parseGoRoute($queryString)
{
    parse_str($queryString, $q);
    unset($q['_path']);

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
    header("Location: ". $url);
    exit;
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
        "username" => trim(_get('user')),
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


function get_router_url(string $path): string
{
    $prefix = get_unsafe_mttinfo('uri');
    if (!defined('MTT_USE_REWRITE') || !MTT_USE_REWRITE) {
        return $prefix. '?_path=/'. $path;
    }
    else {
        return $prefix. $path;
    }
}

function router_url(string $path)
{
    echo htmlspecialchars(get_router_url($path));
}

function get_go_prefix()
{
    $prefix = get_unsafe_mttinfo('uri');
    if (!defined('MTT_USE_REWRITE') || !MTT_USE_REWRITE) {
        return $prefix. '?';
    }
    else {
        return $prefix .= 'go?';
    }
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
