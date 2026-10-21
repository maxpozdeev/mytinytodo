<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (PHP_VERSION_ID < 70400) {
    die("PHP 7.4 or above is required");
}


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
require_once(MTTINC. 'class.lang.php');
require_once(MTTINC. 'notifications.php');
require_once(MTTINC. 'filters.php');
require_once(MTTINC. 'markup.php');
require_once(MTTINC. 'entities.php');
require_once(MTTINC. 'repository.list.php');
require_once(MTTINC. 'repository.tag.php');
require_once(MTTINC. 'repository.task.php');
require_once(MTTINC. 'repository.user.php');


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
