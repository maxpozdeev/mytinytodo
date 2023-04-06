<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (!defined('MTTPATH')) {
    die("Unexpected usage.");
}

require_once('class.controller.php');
require_once('class.updater.php');

function mtt_ext_updater_instance(): MTTExtension
{
    return new UpdaterExtension();
}

use UpdaterExtension\Controller;
use UpdaterExtension\Updater;

class UpdaterExtension extends MTTExtension implements MTTExtensionSettingsInterface, MTTHttpApiExtender
{
    //the same as dir name
    const bundleId = 'updater';

    // settings domain
    const domain = "ext.updater.json";

    function init()
    {
    }

    // MTTHttpApiExtender
    function extendHttpApi(): array
    {
        return array(
            '/check' => [
                'POST'  => [ Controller::class , 'postCheck' ],
            ],
            '/update' => [
                'POST'  => [ Controller::class , 'postUpdate' ],
            ]
        );
    }

    function settingsPage(): string
    {
        $e = function($s) { return __($s, true); };
        $ext = htmlspecialchars(self::bundleId);
        $prefs = self::preferences();
        $lastCheck = $prefs['lastCheck'] ?? 0;
        $version =  $prefs['version'] ?? '';
        $updateStr = '';
        $curVersion = htmlspecialchars(mytinytodo\Version::VERSION);
        $err = null;
        if (time() - $lastCheck > 86400*7) {
            $updater = new Updater;
            $a = $updater->lastVersionInfo();
            if ($a) {
                $lastCheck = $prefs['lastCheck'] = time();
                $version = $prefs['version'] = $a['version'] ?? '';
                $prefs['download'] = $a['download'] ?? '';
                Config::saveDomain(self::domain, $prefs);
            }
            else {
                $err = $updater->lastErrorString;
            }
        }
        $warning = '';
        if ($version != '') {
            if ( version_compare($version, mytinytodo\Version::VERSION) > 0 ) {
                $updateStr = "<br> {$e('updater.updatet_version_avaialable')}: ". htmlspecialchars($version);
                # allow update to v1.7.x only
                if ("1.7." == substr($version, 0, 4)) {
                    $updateStr .= "<br><br>\n <a href=\"#\" data-ext-settings-action=\"post:update\" data-ext=\"$ext\">{$e('updater.update')}</a> ";
                }
                $retval = 0;
                $output = null;
                unset($output);
                @exec('tar --version', $output, $retval);
                if ($retval != 0) {
                    $warning = "<div class=\"tr\"><div style=\"width:100%;text-align:center;\">⚠️ {$e('updater.tarwarning')}</div></div>";
                }
            }
            else {
                $updateStr = "<br>{$e('updater.no_updates')}";
            }
        }
        $lastCheckStr = $err ? $e('updater.download_error') : ($lastCheck ? timestampToDatetime($lastCheck, true) : "");

        if (!boolval(ini_get('allow_url_fopen'))) {
            $warning .= "<div class=\"tr\"><div style=\"width:100%;text-align:center;\">⚠️ {$e('updater.urlconfigwarning')}</div></div>";
        }


        return
<<<EOD
$warning
<div class="tr">
 <div class="th"> {$e('updater.h_check_updates')} </div>
 <div class="td">
    {$e('updater.current_version')}: $curVersion <br>
    {$e('updater.last_checked')}: $lastCheckStr &nbsp; <button type=button data-ext-settings-action="post:check" data-ext="$ext">{$e('updater.check')}</button> <br>
    $updateStr
 </div>
</div>
EOD;
    }

    function saveSettings(array $params, ?string &$outMessage): bool
    {
       return true;
    }


    static function preferences(): array
    {
        $prefs = Config::requestDomain(self::domain);
        return $prefs;
    }
}
