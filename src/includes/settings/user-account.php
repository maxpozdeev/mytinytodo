<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

final class UserAccountSettings {
    private static array $data;
    static function load()
    {
        $db = DBConnection::instance();
        $r = $db->sqa("SELECT id,username,email,pwhash FROM {$db->prefix}users WHERE id=?", [userId()]);
        if (!$r) {
            die("User not found");
        }
        static::$data = $r;
    }
    static function get(string $key): string
    {
        if ($key === 'pwhash')
            return '';
        return (string) (static::$data[$key] ?? '');
    }

    static function exitError(string $error) {
        ErrorApiResponse::exitWithMessage($error, 200);
    }
    static function exitOk(bool $saved = true, ?string $message = null) {
        $data = [
            'ok' => true,
            'saved' => $saved ? 1 : 0,
        ];
        if (!is_null($message)) {
            $data['msg'] = $message;
        }
        (new JsonApiResponse($data))->exit();
    }

    static function editUsername(string $username)
    {
        $errPrefix = __("cantChange", false, __("username"));
        if ($username === '') {
            static::exitError($errPrefix. " ". __("emptyValue"));
        }
        if ($username === static::$data['username']) {
            static::exitOk(false, "Nothing to change");
        }
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
            static::exitError($errPrefix. " ". __("incorrectFormat"));
        }
        $db = DBConnection::instance();
        $id = (int)$db->sq("SELECT id FROM {$db->prefix}users WHERE username = ?", [$username]);
        if ($id && $id !== userId()) {
            static::exitError($errPrefix. " ". __("alreadyInUseByAccount"));
        }
        $db->ex("UPDATE {$db->prefix}users SET username=? WHERE id=?", [$username, userId()]);
        $_SESSION['username'] = $username;
        static::exitOk();
    }

    static function editEmail(string $email)
    {
        $errPrefix = __("cantChange", false, __("email"));
        if ($email === '') {
            static::exitError($errPrefix. " ". __("emptyValue"));
        }
        if ($email === static::$data['email']) {
            static::exitOk(false, "Nothing to change");
        }
        if ( ! isValidEmail($email) ) {
            static::exitError($errPrefix. " ". __("incorrectFormat"));
        }
        $db = DBConnection::instance();
        $id = (int)$db->sq("SELECT id FROM {$db->prefix}users WHERE email = ?", [$email]);
        if ($id && $id !== userId()) {
            static::exitError($errPrefix. " ". __("alreadyInUseByAccount"));
        }
        $db->ex("UPDATE {$db->prefix}users SET email=? WHERE id=?", [$email, userId()]);
        static::exitOk();
    }

    static function editPassword(
        #[SensitiveParameter]
        string $password,
        #[SensitiveParameter]
        string $newpassword,
        #[SensitiveParameter]
        string $newpassword2)
    {
        $errPrefix = __("cantChange", false, __("password"));
        if ($newpassword !== $newpassword2) {
            static::exitError($errPrefix. " ". __("wrongConfirmPassword"));
        }
        if ($newpassword === '') {
            static::exitError($errPrefix. " ". __("emptyValue"));
        }
        if (!isPasswordEqualsToHash($password, static::$data['pwhash'])) {
            static::exitError($errPrefix. " ". __("invalidPassword"));
        }
        $db = DBConnection::instance();
        $hash = passwordHash($newpassword);
        $pwtoken = randomToken();
        $db->ex("UPDATE {$db->prefix}users SET pwhash = ?, pwtoken = ? WHERE id=?", [$hash, $pwtoken, userId()]);
        MTTVars::$userPwToken = $pwtoken;
        $_SESSION['sign'] = sessionSignature($pwtoken);
        static::exitOk();
    }
}

UserAccountSettings::load();

function _c(string $key)
{
    echo htmlspecialchars(UserAccountSettings::get($key));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && defined('MTT_DEMO')) {
    jsonExit([
        'ok' => true,
        'saved' => 0,
    ]);
}

if (isset($_POST['edit_username'])) {
    UserAccountSettings::editUsername( _post('username') );
}
else if (isset($_POST['edit_email'])) {
    UserAccountSettings::editEmail( _post('email') );
}
else if (isset($_POST['edit_password'])) {
    UserAccountSettings::editPassword(_post('password'), _post('newpassword'), _post('newpassword2'));
}


?>

<h4> <?php _e('set_account');?> </h4>

<div class="mtt-settings-table">

<div class="tr">
  <div class="th"><?php _e('username');?></div>
  <div class="td">
    <form action="<?php mtt_settings_page_url(); ?>" method="post" data-ok-reload="yes">
    <input type="hidden" name="edit_username" value="1">
    <input name="username" value="<?php _c('username'); ?>" class="in350"> <br>
    <div class="form-row-buttons"><button type="submit"><?php _e('set_save'); ?></button></div>
    </form>
  </div>
</div>

<div class="tr">
  <div class="th"><?php _e('email');?></div>
  <div class="td">
    <form action="<?php mtt_settings_page_url(); ?>" method="post" data-ok-reload="yes">
    <input type="hidden" name="edit_email" value="1">
    <input type="email" name="email" value="<?php _c('email'); ?>" class="in350">
    <div class="form-row-buttons"><button type="submit"><?php _e('set_save'); ?></button></div>
    </form>
  </div>
</div>

<div class="tr">
  <div class="th"><?php _e('password');?></div>
  <div class="td">
    <form action="<?php mtt_settings_page_url(); ?>" method="post" data-ok-reload="yes">
    <input type="hidden" name="edit_password" value="1">
    <?php _e('password'); ?> <br>
    <input type="password" name="password" value="" class="in350" autocomplete="current-password"> <br>
    <?php _e('new_password'); ?> <br>
    <input type="password" name="newpassword" value="" class="in350" autocomplete="new-password"> <br>
    <?php _e('confirm_new_password'); ?> <br>
    <input type="password" name="newpassword2" value="" class="in350" autocomplete="new-password">
    <div class="form-row-buttons"><button type="submit"><?php _e('set_save'); ?></button></div>
     </form>
  </div>
</div>

</div>
</form>
