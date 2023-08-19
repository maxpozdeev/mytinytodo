<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (!defined('MTTPATH')) {
    die("Unexpected usage.");
}

if (!function_exists('mb_internal_encoding')) {
    throw new Exception("Required PHP module is not found: mbstring");
}
if (strtoupper(mb_internal_encoding()) != 'UTF-8') {
    throw new Exception("mb_internal_encoding is not UTF-8");
}

require_once('class.observer.php');
require_once('class.controller.php');
require_once('class.sender.php');
require_once('class.telegramapi.php');


// on PHP-FPM we can send telegrams on didFinishRequest without delay

// folder is the bundleId of extension
// name of function for extension loader has format "mtt_ext_${bundleId}_loader"

function mtt_ext_notifications_instance(): MTTExtension
{
    return new NotificationsExtension();
}

use Notify\NotificationObserver;
use Notify\Controller;
use Notify\TelegramApi;

class NotificationsExtension extends MTTExtension implements MTTHttpApiExtender, MTTExtensionSettingsInterface
{
    //the same as dir name
    const bundleId = 'notifications';

    // settings domain
    const domain = "ext.notifications.json";

    function init()
    {
        // subscribe for notifications
        MTTNotificationCenter::addObserverForNotifications(
            [ MTTNotification::didCreateTask, MTTNotification::didCreateList ],
            new NotificationObserver()
        );
    }

    // produces smth like like <API_PATH>/ext/notifications/deactivate
    function extendHttpApi(): array
    {
        return array(
            '/deactivate' => [
                'POST'  => [ Controller::class , 'postDeactivateAll' ],
            ],
            '/check' => [
                'POST'  => [ Controller::class , 'postCheck' ],
            ]
        );
    }

    function settingsPage(): string
    {
        $e = function($s) { return __($s, true); };
        $ext = htmlspecialchars(self::bundleId);
        $prefs = self::preferences();
        $emails =  htmlspecialchars( implode(', ', $prefs['emails']) );
        $mailfrom = htmlspecialchars($prefs['mailfrom'] ?? '');
        $mailfromDefault = htmlspecialchars(Notify\Sender::suggestedMailFrom());
        $numberOfChats = count($prefs['chats']);

        $token = $prefs['token'] ?? '';
        if (defined('MTT_DEMO') && $token != '') {
            $token = "<demo>";
        }
        $token = htmlspecialchars($token);

        $botname = $prefs['botname'] ?? '';
        $botLink = '';
        if ($botname != '') {
            $botname = htmlspecialchars($botname);
            $botLink = "<a href='https://t.me/$botname' target='_blank'>@$botname</a>";

            $code = $prefs['code'] ?? null;
            $codeExpires = $prefs['codeExpires'] ?? 0;
            if ($code === null || $codeExpires < time()) {
                $prefs['code'] = $code = randomString(6, '0123456789');
                $prefs['codeExpires'] = $codeExpires = time() + 60*15; // 15 min
                Config::saveDomain(self::domain, $prefs);
            }
            $newChat = "$botLink <br><br>$code &nbsp; <a href=\"#\" data-ext-settings-action=\"post:check\" data-ext=\"$ext\">{$e('notifications.check')}</a>";
        }
        else {
            $newChat = $e('notifications.bot_not_configured');
        }

        $warning = '';
        if (!boolval(ini_get('allow_url_fopen'))) {
            $warning = "<div class=\"tr\"><div style=\"width:100%;text-align:center;\">⚠️ {$e('notifications.urlconfigwarning')}</div></div>";
        }

        //$e = function($s) { return __($s, true); };
        //$c = function($key) { return htmlspecialchars(Config::get($key)); };

        return
<<<EOD
$warning
<div class="tr">
 <div class="th"> {$e('notifications.h_email')}
  <div class="descr">{$e('notifications.d_email')}</div>
 </div>
 <div class="td"> <input name="emails" value="$emails" class="in350" autocomplete="off"> </div>
</div>
<div class="tr">
 <div class="th"> {$e('notifications.h_mailfrom')}
  <div class="descr">{$e('notifications.d_mailfrom')}</div>
 </div>
 <div class="td"> <input name="mailfrom" value="$mailfrom" class="in350" autocomplete="email" placeholder="$mailfromDefault"> </div>
</div>
<div class="tr">
 <div class="th"> {$e('notifications.h_telegram')} </div>
</div>
<div class="tr">
 <div class="th"> {$e('notifications.h_token')}
  <div class="descr">{$e('notifications.d_token')}</div>
 </div>
 <div class="td"> <input name="token" value="$token" class="in350" autocomplete="off"> </div>
</div>
<div class="tr">
 <div class="th"> {$e('notifications.h_active_chats')}
  <div class="descr">{$e('notifications.d_active_chats')}</div>
 </div>
 <div class="td"> $numberOfChats &nbsp; <a href="#" data-ext-settings-action="post:deactivate" data-ext="$ext">{$e('notifications.deactivate_all')}</a> </div>
</div>
<div class="tr">
 <div class="th"> {$e('notifications.h_new_chat')}
  <div class="descr">{$e('notifications.d_new_chat')}</div>
 </div>
 <div class="td"> $newChat </div>
</div>
EOD;
    }

    function settingsPageType(): int
    {
        return 0; //default page
    }

    function saveSettings(array $params, ?string &$outMessage): bool
    {
        if (defined('MTT_DEMO')) {
            $outMessage = "Demo";
            return true;
        }
        $token = $params['token'] ?? '';
        $emails = $params['emails'] ?? '';
        $mailfrom = $params['mailfrom'] ?? '';
        if (!is_string($token) || !is_string($emails) || !is_string($mailfrom)) {
            throw new Exception("Invalid format");
        }

        $prefs = Config::requestDomain(self::domain);
        if ($token !== ($prefs['token'] ?? '')) {
            $prefs['botname'] = '';
            $prefs['chats'] = [];
            $prefs['validToken'] = false;
        }
        $prefs['token'] = $token;
        $prefs['code'] = null;
        $prefs['emails'] = [];
        $prefs['mailfrom'] = str_replace(["\r", "\n", ":", "\"", "'", "?"], '', trim($mailfrom));

        // validate emails
        if ($emails != '') {
            $a = explode(',', $emails);
            foreach ($a as $email) {
                $email = trim($email);
                if (preg_match('/^[^\s\@\|]+@[^\s\@\|]+$/', $email)) {
                    $prefs['emails'][] = $email;
                }
                else {
                    $outMessage = __('notifications.invalid_email');
                    return false;
                }
            }
        }


        // validate token
        if ($token != '' && !$prefs['validToken']) {
            $api = new TelegramApi($token);
            $api->logApiErrors = true;
            $api->throwExceptionOnApiError = true;
            try {
                $result = $api->getMe();
                if ($result && isset($result['username'])) {
                    $prefs['botname'] = $result['username'];
                }
                $prefs['validToken'] = true;
            }
            catch (Exception $e) {
                $prefs['token'] = '';
                $outMessage = __('notifications.no_bot_info');
                if (MTT_DEBUG) {
                    $outMessage .= " (". $e->getMessage(). ")";
                }
                return false;
            }
        }

        Config::saveDomain(self::domain, $prefs);
        $outMessage = __('notifications.saved');
        return true;
    }

    static function preferences(): array
    {
        $prefs = Config::requestDomain(self::domain);
        if (!isset($prefs['chats']) || !is_array($prefs['chats'])) {
            $prefs['chats'] = [];
        }
        if (!isset($prefs['emails']) || !is_array($prefs['emails'])) {
            $prefs['emails'] = [];
        }
        return $prefs;
    }
}
