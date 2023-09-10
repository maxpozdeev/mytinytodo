<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace BackupExtension;

use BackupExtension;
use BackupExtension\Backup;
use BackupExtension\Download;
use BackupExtension\Check;

class Controller extends \ApiController
{
    function postMakeBackup()
    {
        require_once('class.backup.php');
        $filename = BackupExtension::backupFilePath();
        $backup = new Backup($filename);

        if (!$backup->makeBackup()) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $backup->lastErrorString ?? '',
            ];
        }

        $this->response->data = [
            'total' => 1,
            'msg' => __("backup.done"),
            'details' => ''
        ];
    }

    function postDownload()
    {
        require_once('class.download.php');
        $filename = BackupExtension::backupFilePath();
        $download = new Download($filename);

        if (!$download->checkFileAccess()) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $download->lastErrorString ?? '',
            ];
            return;
        }
        $this->response->data = [
            'total' => 1,
            'redirect' => $download->downloadUrl()
        ];
    }

    function getDownload()
    {
        require_once('class.download.php');
        $filename = BackupExtension::backupFilePath();
        $download = new Download($filename);

        $ott = (string)_get('t');
        if (!$download->checkFileAccess($ott)) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $download->lastErrorString ?? '',
            ];
            return;
        }
        $download->printFile();
        exit();
    }

    function postRestore()
    {
        require_once('class.restore.php');
        $restore = new Restore();

        if (!$restore->isUploaded()) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $restore->lastErrorString ?? '',
            ];
            return;
        }

        if (!$restore->restore()) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $restore->lastErrorString ?? '',
            ];
            return;
        }

        $this->response->data = [
            'total' => 1,
            'msg' => __("backup.done"),
            'redirect' => get_mttinfo('url'),
        ];
    }

    function postCheckInconsistency()
    {
        require_once('class.check.php');
        $check = new Check();

        if (!$check->check()) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $check->lastErrorString ?? '',
            ];
            return;
        }
        $html = "<pre>". htmlspecialchars($check->report). "</pre>";
        $this->response->data = [
            'total' => 1,
            'msg' => __("backup.done"),
            'html' => $html,
        ];
    }

    function postRepairInconsistency()
    {
        require_once('class.check.php');
        $check = new Check();

        if (!$check->repair()) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $check->lastErrorString ?? '',
            ];
            return;
        }
        $this->response->data = [
            'total' => 1,
            'msg' => __("backup.done"),
        ];
    }

}
