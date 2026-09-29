<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace MTTUpdater;

require_once(MTTINC. 'class.updater.php');

use \Config;

class UpdaterController extends \ApiController implements \MTTControlPanelHttpApiExtender
{
    const domain = 'updater.json';

    static function preferences(): array
    {
        $prefs = Config::requestDomain(self::domain);
        return $prefs;
    }

    function postCheck()
    {
        $prefs = self::preferences();
        $updater = new Updater;
        $a = $updater->lastVersionInfo();
        if ($a) {
            $prefs['lastCheck'] = time();
            $prefs['version'] = $a['version'] ?? '';
            $prefs['download'] = $a['download'] ?? '';
            Config::saveDomain(self::domain, $prefs);
            $this->response->data = [
                'ok' => true ,
                'msg' => __('updater.last_version', true, $prefs['version']),
                'reload' => true,
            ];
        }
        else {
            $this->response->data = [
                'ok' => false,
                'error' => $updater->lastErrorString ?? ''
            ];
        }
    }

    function postUpdate()
    {
        $prefs = self::preferences();
        $url = $prefs['download'] ?? '';
        if ($url == '') {
            $this->response->data = [
                'ok' => false,
                'error' => __("updater.download_error")
            ];
            return;
        }
        $updater = new Updater;
        $file = MTTPATH. 'update.tar.gz';
        if (!$updater->download($url, $file)) {
            $this->response->data = [
                'ok' => false,
                'error' => __("updater.download_error") . $updater->lastErrorString ?? '',
            ];
            return;
        }
        if (!$updater->extractAndReplace($file)) {
            $this->response->data = [
                'ok' => false,
                'error' => __("updater.update_error") . $updater->lastErrorString ?? '',
            ];
            return;
        }
        @unlink($file);

        if (function_exists("opcache_reset")) {
            opcache_reset();
        }

        // TODO: need to run post-update by new version
        // ...
        // remove /includes/lang/cns.json   #renamed to zh-cn.json

        $prefs['version'] = '';
        $prefs['download'] = '';
        $prefs['lastCheck'] = 0;
        Config::saveDomain(self::domain, $prefs);

        $this->response->data = [
            'ok' => true,
            'msg' => __("updater.updated"),
            'reload' => true,
        ];
    }

    static function extendControlPanelHttpApi(): array
    {
        return array(
            '/updater/check'   => ['POST' => [UpdaterController::class, 'postCheck']],
            '/updater/update'  => ['POST' => [UpdaterController::class, 'postUpdate']],
        );
    }
}
