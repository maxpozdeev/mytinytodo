<?php

$dontStartSession = true;
require_once(__DIR__ . '/init.php');

if (!need_auth()) {
    exitmsg("No password protection is set");
}

if (isset($_POST['reset'])) {
    $pass = _post('pass');
    $hash = passwordHash($pass);
    $pwtoken = randomToken();
    $db = DBConnection::instance();
    $db->ex("UPDATE {$db->prefix}users SET pwhash = ?, pwtoken = ? WHERE id = 1", [$hash, $pwtoken]);
    exitmsg("Done");
}
else {
    exitmsg("<form method=post><label>Enter new password of adminstrator:<br><input name=pass type=password> <input name=reset type=submit></label></form>");
}


function exitmsg(?string $text = '') {
    echo "<h1>Password Reset</h1>\n";
    echo $text;
    echo "<br><br><hr> <i>For security reasons delete this file after usage!</i>";
    exit;
}
