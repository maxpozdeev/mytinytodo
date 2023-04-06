<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace UpdaterExtension;

use \UpdaterExtension;
use \Config;

class Controller extends \ApiController
{
    function postCheck()
    {
        $prefs = UpdaterExtension::preferences();
        $updater = new Updater;
        $a = $updater->lastVersionInfo();
        if ($a) {
            $prefs['lastCheck'] = time();
            $prefs['version'] = $a['version'] ?? '';
            $prefs['download'] = $a['download'] ?? '';
            Config::saveDomain(UpdaterExtension::domain, $prefs);
            $this->response->data = [ 'total' => 1 ];
        }
        else {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $updater->lastErrorString ?? ''
            ];
        }
    }

    function postUpdate()
    {
        $prefs = UpdaterExtension::preferences();
        $url = $prefs['download'] ?? '';
        if ($url == '') {
            $this->response->data = [ 'total' => 0, 'msg' => __("updater.download_error") ];
            return;
        }
        $updater = new Updater;
        $file = MTTPATH. 'update.tar.gz';
        if (!$updater->download($url, $file)) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("updater.download_error"),
                'details' => $updater->lastErrorString ?? ''
            ];
            return;
        }
        if (!$updater->extractAndReplace($file)) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("updater.update_error"),
                'details' => $updater->lastErrorString ?? ''
            ];
            return;
        }
        @unlink($file);

        if (function_exists("opcache_reset")) {
            opcache_reset();
        }

        // TODO: need to run post-update by new version
        // ...
        // remove /includes/lang/cns.json   #renamed to zh-cn.jpon

        $prefs['version'] = '';
        $prefs['download'] = '';
        $prefs['lastCheck'] = 0;
        Config::saveDomain(UpdaterExtension::domain, $prefs);

        $this->response->data = [ 'total' => 1, 'msg' => __("updater.updated"), 'reload' => 'ext-settings' ];
    }


}
