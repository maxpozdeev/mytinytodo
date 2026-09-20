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
        $userRepo = new UserRepo(DBConnection::instance());
        $r = $userRepo->userDataById(userId());
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

    static function getExtra(): ?array
    {
        $extra = json_decode(static::$data['extra'] ?? '', true);
        if (!$extra) {
            return null;
        }
        return $extra;
    }

    static function getAppPasswords(): ?array
    {
        $extra = static::getExtra() ?? [];
        if (!isset($extra['apppasswords']) || !is_array($extra['apppasswords'])) {
            return null;
        }
        return $extra['apppasswords'];
    }

    static function exitError(string $error) {
        ErrorApiResponse::exitWithMessage($error, 200);
    }

    static function exitOk(bool $saved = true, ?string $message = null, ?array $additional = null) {
        $data = [
            'ok' => true,
            'saved' => $saved ? 1 : 0,
        ];
        if (!is_null($message)) {
            $data['msg'] = $message;
        }
        if ($additional) {
            foreach ($additional as $k => $v) {
                $data[$k] = $v;
            }
        }
        (new JsonApiResponse($data))->exit();
    }

    static function editName(string $name)
    {
        $errPrefix = __("cantChange", false, __("name"));
        if ($name === '') {
            static::exitError($errPrefix. " ". __("emptyValue"));
        }
        if ($name === static::$data['name']) {
            static::exitOk(false, __("nothingToChange"));
        }
        if (preg_match("/[<>]$/", $name)) {
            static::exitError($errPrefix. " ". __("incorrectFormat"));
        }
        $db = DBConnection::instance();
        $db->ex("UPDATE {$db->prefix}users SET name=? WHERE id=?", [$name, userId()]);
        MTTVars::$user = $name;
        static::exitOk();
    }

    static function editUsername(string $username)
    {
        $errPrefix = __("cantChange", false, __("username"));
        if ($username === '') {
            static::exitError($errPrefix. " ". __("emptyValue"));
        }
        if ($username === static::$data['username']) {
            static::exitOk(false, __("nothingToChange"));
        }
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
            static::exitError($errPrefix. " ". __("incorrectFormat"));
        }
        $db = DBConnection::instance();
        $id = (int) (new UserRepo($db))->findUserIdByUsername($username);
        if ($id && $id !== userId()) {
            static::exitError($errPrefix. " ". __("alreadyInUseByAccount"));
        }
        $db->ex("UPDATE {$db->prefix}users SET username=? WHERE id=?", [$username, userId()]);
        MTTVars::$username = $username;
        static::exitOk();
    }

    static function editEmail(string $email)
    {
        $errPrefix = __("cantChange", false, __("email"));
        if ($email === '') {
            static::exitError($errPrefix. " ". __("emptyValue"));
        }
        if ($email === static::$data['email']) {
            static::exitOk(false, __("nothingToChange"));
        }
        if ( ! isValidEmail($email) ) {
            static::exitError($errPrefix. " ". __("incorrectFormat"));
        }
        $db = DBConnection::instance();
        $id = (int) (new UserRepo($db))->findUserIdByEmail($email);
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

    static function createAppPassword(string $name)
    {
        if ($name === '') {
            $name = "New password";
        }

        $extra = static::getExtra() ?? [];
        $newPass = randomString2(24);
        $newHash = passwordHash($newPass);

        $passwords = static::getAppPasswords() ?? [];
        $passwords[] = [
            "hash" => $newHash,
            "name" => $name,
            "created" => time(),
            "uuid" => generateUUID(),
        ];
        $extra['apppasswords'] = $passwords;
        $json = json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $db = DBConnection::instance();
        $db->ex("UPDATE {$db->prefix}users SET extra=? WHERE id=?", [$json, userId()]);

        $msg = __('apppassword_created', true);
        $msg = sprintf($msg, "<b>". htmlspecialchars($newPass). "</b>");
        static::exitOk(true, $msg, ['isHtmlMsg'=>true]);
    }

    static function revokeAppPassword(string $uuid)
    {
        $errPrefix = __("cantChange", false, __("appPassword"));
        if ($uuid === '') {
            static::exitError($errPrefix. " ". __("emptyValue"));
        }
        $extra = static::getExtra() ?? [];
        $passwords = static::getAppPasswords() ?? [];
        $count = count($passwords);
        $passwords = array_filter($passwords, function ($row) use ($uuid) {
            if (isset($row['uuid']) && $row['uuid'] === $uuid) {
                return false;
            }
            return true;
        });
        if (count($passwords) === $count) {
            static::exitError($errPrefix. " ". __("nothingToChange"));
        }
        $extra['apppasswords'] = $passwords;
        $json = json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $db = DBConnection::instance();
        $db->ex("UPDATE {$db->prefix}users SET extra=? WHERE id=?", [$json, userId()]);
        static::exitOk();
    }
}

UserAccountSettings::load();

function _c(string $key)
{
    echo htmlspecialchars(UserAccountSettings::get($key));
}


if (isset($_POST['edit_name'])) {
    UserAccountSettings::editName( _post('name') );
}
else if (isset($_POST['edit_username'])) {
    UserAccountSettings::editUsername( _post('username') );
}
else if (isset($_POST['edit_email'])) {
    UserAccountSettings::editEmail( _post('email') );
}
else if (isset($_POST['edit_password'])) {
    UserAccountSettings::editPassword(_post('password'), _post('newpassword'), _post('newpassword2'));
}
else if (isset($_POST['new_ap'])) {
    UserAccountSettings::createAppPassword(_post('ap_name'));
}
else if (isset($_POST['revoke_ap'])) {
    UserAccountSettings::revokeAppPassword(_post('revoke_ap'));
}
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    jsonExit([
        'ok' => false
    ]);
}


function printAppPasswordsTable()
{
    $passwords = UserAccountSettings::getAppPasswords() ?? [];
    if (!$passwords) {
        return;
    }
    print "<table style='margin-top:1rem;'><tr><th style='min-width:150px;'>".__('th_name', true).
        "</th><th>".__('th_created', true)."</th><th></th></tr>";

    foreach ($passwords as $row) {
        $name = htmlspecialchars($row['name']);
        $created = htmlspecialchars(timestampToDatetime( (int)($row['created'] ?? 0) ));
        $uuid = htmlspecialchars($row['uuid'] ?? '');
        print "<tr><td>$name</td><td>$created</td><td>".
            '<form action="'. mtt_get_settings_page_url(). '" method="post" data-ok-reload="yes">'.
            '<input type="hidden" name="revoke_ap" value="'. $uuid. '">'.
            "<button>". __('btn_revoke', true). "</button></form></td></tr>";
    }

    print "</table>";
}

?>

<h4> <?php _e('set_account');?> </h4>

<div class="mtt-settings-table">

<div class="tr">
  <div class="th"><?php _e('name');?></div>
  <div class="td">
    <form action="<?php mtt_settings_page_url(); ?>" method="post" data-ok-reload="yes">
    <input type="hidden" name="edit_name" value="1">
    <input name="name" value="<?php _c('name'); ?>" class="in350"> <br>
    <div class="form-row-buttons"><button type="submit"><?php _e('set_save'); ?></button></div>
    </form>
  </div>
</div>

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

<div class="tr">
  <div class="th"><?php _e('set_apppasswords_h');?></div>
  <div class="td">
    <form action="<?php mtt_settings_page_url(); ?>" method="post" data-ok-reload="yes">
    <input type="hidden" name="new_ap" value="1">
    <input type="text" name="ap_name" value="" class="in350" placeholder="<?php _e('set_apppassword_placeholder'); ?>">
    <div class="form-row-buttons"><button type="submit"><?php _e('btn_add'); ?></button></div>
    </form>
    <?php printAppPasswordsTable(); ?>
  </div>
</div>

</div>
</form>
