#!/usr/bin/env php
<?php

if ( PHP_SAPI !== 'cli' ) {
    die("Run from command line only!");
}

if ( $argc < 3 ) {
    die("Usage:\n".
        "  mtt-cmd.php read <parameter> \n".
        "  mtt-cmd.php write <parameter> <value>\n".
        "  mtt-cmd.php adduser <username> [email]\n".
        "  mtt-cmd.php deluser <username> \n".
        "  mtt-cmd.php password <username> [password]\n".
        "  mtt-cmd.php email <username> <email>\n"
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
    case 'read': cmd_read($arg1); break;
    case 'write': cmd_write($arg1, $arg2); break;
    case 'adduser': cmd_adduser((string)$arg1, (string)$arg2); break;
    case 'password': cmd_password((string)$arg1, (string)$arg2); break;
    case 'email': cmd_email((string)$arg1, (string)$arg2); break;
    case 'deluser': cmd_deluser((string)$arg1); break;
    default: die("Unknown command: $cmd\n");
}


function cmd_read(string $param) {
    print Config::get($param) . "\n";
}

function cmd_write(string $param, ?string $value) {
    if ($value === null) {
        die("Can not write '$param': value is not specified\n");
    }
    print ("Set '$param' to '$value'\n");
    $config = Config::getConfig();
    $config->set($param, $value);
    AppConfig::saveDomain(Config::appDomain, $config->asArray());
    print ("Done!\n");
}


function cmd_adduser(string $user, string $email = '')
{
    $db =  DBConnection::instance();
    print "Adding user '$user'\n";
    if ($db->sq("SELECT 1 FROM {$db->prefix}users WHERE username = ?", [$user])) {
        die("Error: user already exists\n");
    }
    if ($email == '')
        $email = "$user@localhost";
    $db->ex( "INSERT INTO {$db->prefix}users (username,name,email) VALUES (?,?,?)", [$user, $user, $email]);
    $id = $db->lastInsertId();
    print "User created with ID $id\n";
}

function cmd_deluser(string $user)
{
    $db =  DBConnection::instance();
    print "Deleting user '$user'\n";
    $userId = (int) $db->sq("SELECT id FROM {$db->prefix}users WHERE username = ?", [$user]);
    if (!$userId) {
        die("Error: user does not exists\n");
    }
    if ($userId == 1) {
        die("Error: can not delete administrator\n");
    }
    $db->ex("BEGIN");
    $db->ex("DELETE FROM {$db->prefix}todolist WHERE list_id IN (SELECT id FROM {$db->prefix}lists WHERE user_id=?)", [$userId]);
    $db->ex("DELETE FROM {$db->prefix}tag2task WHERE list_id IN (SELECT id FROM {$db->prefix}lists WHERE user_id=?)", [$userId]);
    $db->ex("DELETE FROM {$db->prefix}tags WHERE user_id=?", [$userId]);
    $db->ex("DELETE FROM {$db->prefix}lists WHERE user_id=?", [$userId]);
    $db->ex("DELETE FROM {$db->prefix}usersettings WHERE user_id=?", [$userId]);
    $db->ex("DELETE FROM {$db->prefix}users WHERE id=?", [$userId]);
    # sessions?
    $db->ex("COMMIT");
    print "User (ID $userId) deleted\n";
}


function cmd_password(
    string $user,
    #[\SensitiveParameter]
    string $pass): void {
    print "Set password for user '$user'\n";
    if ($pass == '') {
        print "Enter a password\n";
        system('stty -echo'); #no windows
        $pass = trim(fgets(STDIN));
        system('stty echo');
    }
    if ($pass == '') {
        die("Error: cant set empty password\n");
    }
    $db =  DBConnection::instance();
    if (!$db->sq("SELECT 1 FROM {$db->prefix}users WHERE username = ?", [$user])) {
        die("Error: user does not exist\n");
    }
    $hash = passwordHash($pass);
    $pwtoken = randomToken();
    $db->ex("UPDATE {$db->prefix}users SET pwhash = ?, pwtoken = ? WHERE username = ?", [$hash, $pwtoken, $user]);
    // delete sessions?
    print "New password set!\n";
}


function cmd_email(string $user, string $email): void {
    print "Set e-mail address for user '$user'\n";
    if ($email == '') {
        die("Error: cant set empty e-mail\n");
    }
    if (!preg_match("/^[a-zA-Z0-9\\._+-]+@[a-zA-Z0-9\\.-]+$/", $email)) {
        die("Error: incorrect e-mail\n");
    }
    $db = DBConnection::instance();
    if (!$db->sq("SELECT 1 FROM {$db->prefix}users WHERE username = ?", [$user])) {
        die("Error: user does not exist\n");
    }
    $db->ex("UPDATE {$db->prefix}users SET email = ? WHERE username = ?", [$email, $user]);
    print "New e-mail set!\n";
}
