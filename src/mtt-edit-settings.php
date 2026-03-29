<?php

if ( !isset($argv) || !isset($argc) || isset($_SERVER['REMOTE_ADDR']) ) {
    die("Run from command line only!");
}
if ( $argc < 3 ) {
    die("Usage:\n".
        "  mtt-edit-settings.php read <parameter> \n".
        "  mtt-edit-settings.php write <parameter> <value>\n".
        "  mtt-edit-settings.php password <password>\n"
    );
}

$dontStartSession = true;
require_once(__DIR__ . '/init.php');

$cmd = $argv[1];
$param = $argv[2];
$value = $argc > 3 ? $argv[3] : null;


switch ($cmd) {
    case 'read': cmd_read($param); break;
    case 'write': cmd_write($param, $value); break;
    case 'password': cmd_password($param); break;
    default: die("Unknown command: $cmd\n");
}


function cmd_read($param) {
    print Config::get($param) . "\n";
}

function cmd_write($param, $value) {
    if ($value === null) {
        die("Can not write '$param': value is not specified\n");
    }
    print ("Set '$param' to '$value'\n");
    Config::set($param, $value);
    Config::save();
    print ("Done!\n");
}

function cmd_password($value) {
    $value = passwordHash($value);
    cmd_write('password', $value);
}
