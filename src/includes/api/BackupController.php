<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/


namespace MTTBackup;

require_once(MTTINC. 'class.backup.backup.php');
require_once(MTTINC. 'class.backup.check.php');
require_once(MTTINC. 'class.backup.download.php');
require_once(MTTINC. 'class.backup.restore.php');

use MTTBackup\Backup;
use MTTBackup\Download;
use MTTBackup\Check;
use MTTBackup\Restore;

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
                'ok' => false,
                'error' => $backup->lastErrorString ?? '',
            ];
        }

        $this->response->data = [
            'ok' => true,
            'msg' => __("backup.done"),
            'reload' => true,
        ];
    }

    function postDownload()
    {
        require_once(MTTINC. 'class.backup.download.php');
        $filename = self::backupFilePath();
        $download = new Download($filename);

        if (!$download->checkFileAccess()) {
            $this->response->data = [
                'ok' => false,
                'error' => $download->lastErrorString ?? '',
            ];
            return;
        }
        $this->response->data = [
            'ok' => true,
            'redirect' => $download->downloadUrl()
        ];
    }

    function getDownloadLink()
    {
        require_once(MTTINC. 'class.backup.download.php');
        $filename = self::backupFilePath();
        $download = new Download($filename);

        $ott = (string)_get('t');
        if (!$download->checkFileAccess($ott)) {
            $this->response->data = [
                'ok' => false,
                'error' => $download->lastErrorString ?? '',
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
                'ok' => false,
                'error' => $restore->lastErrorString ?? '',
            ];
            return;
        }

        if (!$restore->restore()) {
            $this->response->data = [
                'ok' => false,
                'error' => $restore->lastErrorString ?? 'Unknown error',
            ];
            return;
        }

        $this->response->data = [
            'ok' => true,
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
                'ok' => false,
                'error' => $check->lastErrorString ?? '',
            ];
            return;
        }
        $this->response->data = [
            'ok' => true,
            'msg' => __("backup.done"),
        ];
        if ($check->report != 'OK') {
            $this->response->data['html'] = "<pre>". htmlspecialchars($check->report). "</pre>";
        }
    }

    function postRepairInconsistency()
    {
        require_once(MTTINC. 'class.backup.check.php');
        $check = new Check();

        if (!$check->repair()) {
            $this->response->data = [
                'ok' => false,
                'error' => $check->lastErrorString ?? '',
            ];
            return;
        }
        $this->response->data = [
            'ok' => true,
            'msg' => __("backup.done"),
        ];
    }

    static function extendControlPanelHttpApi(): array
    {
        return array(
            '/backup/makeBackup' => ['POST' => [BackupController::class, 'postMakeBackup']],
            '/backup/download' => ['POST' => [BackupController::class, 'postDownload'] ],
            '/backup/downloadLink' => ['GET'  => [BackupController::class, 'getDownloadLink']],
            '/backup/restore' => ['POST' => [BackupController::class, 'postRestore']],
            '/backup/restoreLocal' => ['POST' => [BackupController::class, 'postRestoreLocal']],
            '/backup/checkInconsistency' => ['POST' => [BackupController::class, 'postCheckInconsistency']],
            '/backup/repairInconsistency' => ['POST' => [BackupController::class, 'postRepairInconsistency']],
        );
    }
}
