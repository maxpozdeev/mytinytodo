<?php

if ( PHP_SAPI !== 'cli' ) {
    die("Run from command line only!");
}

if ( $argc < 3 ) {
    die("Usage:\n".
        "  mtt-cmd.php read <parameter> \n".
        "  mtt-cmd.php write <parameter> <value>\n".
        "  mtt-cmd.php password <username> <password>\n".
        "  mtt-cmd.php adduser <username> \n"
    );
}

$dontStartSession = true;
require_once(__DIR__ . '/init.php');

ini_set('display_errors', '0');
ini_set('log_errors', '1');

$cmd = $argv[1];
$arg1 = $argv[2];
$arg2 = $argc > 3 ? $argv[3] : null;


switch ($cmd) {
    case 'read': cmd_read($arg1, $arg2); break;
    case 'write': cmd_write($arg1, $arg2); break;
    case 'adduser': cmd_adduser((string)$arg1); break;
    case 'password': cmd_password((string)$arg1, (string)$arg2); break;
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


function cmd_adduser(string $user)
{
    $db =  DBConnection::instance();
    print "Adding user '$user'\n";
    if ($db->sq("SELECT 1 FROM {$db->prefix}users WHERE username = ?", [$user])) {
        die("Error: user already exists\n");
    }
    $db->ex( "INSERT INTO {$db->prefix}users (username,name) VALUES (?,?,?)", [$user, $user]);
    print "User created\n";
}


function cmd_password(string $user, string $pass) {
    print "Set password for user '$user'\n";
    if ($pass == '') {
        die("Error: cant set empty password\n");
    }
    $db =  DBConnection::instance();
    if (!$db->sq("SELECT 1 FROM {$db->prefix}users WHERE username = ?", [$user])) {
        die("Error: user does not exist\n");
    }
    $hash = passwordHash($pass);
    $db->ex("UPDATE {$db->prefix}users SET pwhash = ? WHERE username = ?", [$hash, $user]);
    print "New password set!\n";
}
