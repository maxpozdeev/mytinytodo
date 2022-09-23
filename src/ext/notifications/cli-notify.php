<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

set_time_limit(30);

if (php_sapi_name() != 'cli') {
    error_log("Supports cli only");
    exit(-1);
}
if (!function_exists('pcntl_fork')) {
    error_log("Required PHP module is not found: pcntl");
    exit(-2);
}
$dontStartSession = 1;
require(__DIR__.'/../../init.php');

$hash = fgets(STDIN);
if ($hash === false) {
    error_log("No input");
    exit(-3);
}
$hash = trim($hash);
$text = stream_get_contents(STDIN);

// Wi will fork a child to do a long work
$pid = pcntl_fork();
if ($pid == -1) {
    error_log("Failed to fork a child");
    exit(-1);
}
else if ($pid) {
    // parent will not wait for child's exit
    exit;
}

// Child is here, detach it
if (posix_setsid() < 0) {
    error_log("posix_setsid() failed");
    exit;
}

$prefs = NotificationsExtension::preferences();
if (!isset($prefs['token'])) {
    error_log("No telegram token");
    exit(-4);
}
$token = $prefs['token'] ?? '';
if (!password_verify($prefs['token'], $hash)) {
    error_log("Not authorized");
    exit(-5);
}

$sender = new Notify\Sender($prefs);
$sender->sendTelegramsWithApi($text);
