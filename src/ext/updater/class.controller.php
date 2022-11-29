<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
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
        $a = UpdaterExtension::lastVersionInfo();
        if ($a) {
            $prefs['lastCheck'] = time();
            $prefs['version'] = $a['version'] ?? '';
            $prefs['download'] = $a['download'] ?? '';
            Config::saveDomain(UpdaterExtension::domain, $prefs);
        }
        $this->response->data = [ 'total' => 1 ];
    }

    function postUpdate()
    {
        $prefs = UpdaterExtension::preferences();
        $url = $prefs['download'] ?? '';
        if ($url == '') {
            $this->response->data = [ 'total' => 0, 'msg' => __("updater.download_error") ];
            return;
        }
        $file = MTTPATH. 'update.tar.gz';
        $error = null;
        if (!UpdaterExtension::download($url, $file, $error)) {
            $this->response->data = [ 'total' => 0, 'msg' => __("updater.download_error"), 'details' => $error ];
            return;
        }
        $error = null;
        if (!UpdaterExtension::extractAndReplace($file, $error)) {
            $this->response->data = [ 'total' => 0, 'msg' => __("updater.update_error"), 'details' => $error ];
            return;
        }
        @unlink($file);

        if (function_exists("opcache_reset")) {
            opcache_reset();
        }

        // TODO: need to run post-update by new version
        // ...

        $prefs['version'] = '';
        $prefs['download'] = '';
        $prefs['lastCheck'] = 0;
        Config::saveDomain(UpdaterExtension::domain, $prefs);

        $this->response->data = [ 'total' => 1, 'msg' => __("updater.updated"), 'reload' => 'ext-settings' ];
    }


}
