<?php
/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2010,2020-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

$checkDbExists = true;
require_once('./init.php');

//Parse query string
if ( isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '' ) {
    parseRoute($_SERVER['QUERY_STRING']);
}


$lang = Lang::instance();

if ($lang->rtl()) {
    Config::set('rtl', 1);
}

if (!is_int(Config::get('firstdayofweek')) || Config::get('firstdayofweek')<0 || Config::get('firstdayofweek')>6) {
    Config::set('firstdayofweek', 1);
}

if ( access_token() == '' ) {
    update_token();
}

if (MTT_THEME != 'theme') {
    // custom theme
    require(MTT_THEME_PATH. 'index.php');
}
else {
    require(MTTINC. 'theme.php');
}

MTTNotificationCenter::postDidFinishRequestNotification();
// end


function parseRoute($queryString)
{
    parse_str($queryString, $q);
    if (isset($q['list'])) {
        $hash = ($q['list'] == 'alltasks') ? ['alltasks'] : ['list', (int)$q['list']];
        unset($q['list']);
        redirectWithHashRoute($hash, $q);
    }
    else if (isset($q['task'])) {
        $listId = (int)DBCore::defaultInstance()->getListIdByTaskId((int)$q['task']);
        if ($listId > 0) {
            $h = [ 'list', $listId, 'search', '#'. (int)$q['task']];
            redirectWithHashRoute($h);
        }
        // TODO: not found
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
        "title" => get_unsafe_mttinfo('title'),
        "lang" => Lang::instance()->jsStrings(),
        "mttUrl" => get_mttinfo('mtt_uri'),
        "homeUrl" => $homeUrl,
        "apiUrl" => get_mttinfo('api_url'),
        "needAuth" => need_auth() ? true : false,
        "isLogged" => is_logged() ? true : false,
        "showdate" => Config::get('showdate') ? true : false,
        "showtime" => Config::get('showtime') ? true : false,
        "duedatepickerformat" => htmlspecialchars(Config::get('dateformat2')),
        "firstdayofweek" => (int) Config::get('firstdayofweek'),
        "calendarIcon" => get_mttinfo('theme_url'). 'images/calendar.svg',
        "autotag" => Config::get('autotag') ? true : false,
        "markdown" => Config::get('markup') == 'v1' ? false : true
    );
    $json = json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        error_log("MTT Error: Failed to encode array of options to JSON. Code: ". (int)json_last_error());
        echo "{}";
    }
    else {
        echo $json;
    }
}
