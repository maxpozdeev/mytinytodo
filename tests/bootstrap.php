<?php

if (getenv('MTT_PATH') !== false) {
    define('MTTPATH', getenv('MTT_PATH'));
}

if (!defined('MTTPATH')) {
    define('MTTPATH', realpath(dirname(__DIR__)) .'/src/');
}

if (!file_exists(MTTPATH. 'config.php')) {
    //die("No config.php in the root\n");
    if (!copy(__DIR__ . '/config-sqlite.php', MTTPATH. 'config.php')) {
        die("Failed to copy config-sqlite.php");
    }
}

require_once(MTTPATH. 'load.php');

error_reporting(E_ALL);
