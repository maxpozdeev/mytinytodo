<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/


namespace Backup;

require_once(MTTINC. 'class.backup.backup.php');
require_once(MTTINC. 'class.backup.check.php');
require_once(MTTINC. 'class.backup.download.php');
require_once(MTTINC. 'class.backup.restore.php');

use Backup\Backup;
use Backup\Download;
use Backup\Check;
use Backup\Restore;

class BackupController extends \ApiController implements \MTTControlPanelHttpApiExtender
{
    static function backupFilePath(): string
    {
        return MTTPATH. 'db/backup.xml';
    }

    function postMakeBackup()
    {
        require_once(MTTINC. 'class.backup.backup.php');
        $filename = self::backupFilePath();
        $tmpFile = null;
        if (file_exists('/run/.containerenv') || file_exists('/.dockerenv')) {
            $tmpFile = '/tmp/mtt-backup.xml';
        }
        $backup = new Backup($filename, $tmpFile);

        if (!$backup->makeBackup()) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $backup->lastErrorString ?? '',
            ];
        }

        $this->response->data = [
            'total' => 1,
            'ok' => true,
            'msg' => __("backup.done"),
            'alertTextOnLoad' => __("backup.done"),
        ];
    }

    function postDownload()
    {
        require_once(MTTINC. 'class.backup.download.php');
        $filename = self::backupFilePath();
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
        require_once(MTTINC. 'class.backup.download.php');
        $filename = self::backupFilePath();
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

    function postRestore(bool $isLocal = false)
    {
        require_once(MTTINC. 'class.backup.backup.php');
        require_once(MTTINC. 'class.backup.restore.php');
        $restore = new Restore();

        $filePresent = false;
        if ($isLocal) {
            $filePresent = $restore->isLocal(self::backupFilePath());
        }
        else        {
            $filePresent = $restore->isUploaded();
        }
        if (!$filePresent) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $restore->lastErrorString ?? '',
            ];
            return;
        }

        if (!$restore->restore()) {
            $this->response->data = [
                'total' => 1,
                'ok' => true,
                'alertText' => $restore->lastErrorString ?? 'Unknown error',
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

    function postRestoreLocal()
    {
        return $this->postRestore(true);
    }

    function postCheckInconsistency()
    {
        require_once(MTTINC. 'class.backup.check.php');
        $check = new Check();

        if (!$check->check()) {
            $this->response->data = [
                'total' => 0,
                'msg' => __("error"),
                'details' => $check->lastErrorString ?? '',
            ];
            return;
        }
        $this->response->data = [
            'total' => 1,
            'ok' => true,
            'msg' => __("backup.done"),
        ];
        if ($check->report == 'OK') {
            $this->response->data['alertText'] = "OK";
        }
        else {
            $this->response->data['html'] = "<pre>". htmlspecialchars($check->report). "</pre>";
            //$this->response->data['alertText'] = $check->report;
        }
    }

    function postRepairInconsistency()
    {
        require_once(MTTINC. 'class.backup.check.php');
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
            'ok' => true,
            'msg' => __("backup.done"),
            'alertText' => __("backup.done"),
        ];
    }

    static function extendControlPanelHttpApi(): array
    {
        return array(
            '/backup/makeBackup' => ['POST' => [BackupController::class, 'postMakeBackup']],
            '/backup/download' => [
                'POST' => [BackupController::class, 'postDownload'],
                'GET'  => [BackupController::class, 'getDownload', true],
            ],
            '/backup/restore' => ['POST' => [BackupController::class, 'postRestore']],
            '/backup/restoreLocal' => ['POST' => [BackupController::class, 'postRestoreLocal']],
            '/backup/checkInconsistency' => ['POST' => [BackupController::class, 'postCheckInconsistency']],
            '/backup/repairInconsistency' => ['POST' => [BackupController::class, 'postRepairInconsistency']],
        );
    }
}
