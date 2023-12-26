<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (!defined('MTTPATH')) {
    die("Unexpected usage.");
}

require_once('class.controller.php');

function mtt_ext_backup_instance(): MTTExtension
{
    return new BackupExtension();
}

use BackupExtension\Controller;

class BackupExtension extends MTTExtension implements MTTExtensionSettingsInterface, MTTHttpApiExtender
{
    //the same as dir name
    const bundleId = 'backup';

    // settings domain
    const domain = "ext.backup.json";

    function init() {
    }

    // MTTHttpApiExtender
    function extendHttpApi(): array
    {
        return array(
            '/makeBackup' => [
                'POST'  => [ Controller::class , 'postMakeBackup' ],
            ],
            '/download' => [
                'POST'  => [ Controller::class , 'postDownload' ],
                'GET'  =>  [ Controller::class , 'getDownload', true ], // doesn't check auth token
            ],
            '/restore' => [
                'POST'  => [ Controller::class , 'postRestore' ],
            ],
            '/checkInconsistency' => [
                'POST' => [ Controller::class , 'postCheckInconsistency' ],
            ],
            '/repairInconsistency' => [
                'POST' => [ Controller::class , 'postRepairInconsistency' ],
            ],

        );
    }

    function settingsPage(): string
    {
        $warning = '';
        $e = function($s, $arg=null) { return __($s, true, $arg); };
        $ext = htmlspecialchars(self::bundleId);

        $downloadDisabled = '';
        $lastBackup = '';
        $filename = MTTPATH. 'db/backup.xml';
        if (file_exists($filename)) {
            $time = filemtime($filename);
            $lastBackup = htmlspecialchars( sprintf($e('backup.last_backup'), formatTime(Config::get('dateformat'). " H:i:s", $time)) );
        }
        else {
            $downloadDisabled = 'disabled';
        }

        return <<<EOD
$warning
<script>
function onBackupFileChange(el) {
    const fd = new FormData();
    fd.append('file', el.files[0]);
    mytinytodo.extensionSettingsAction(el.dataset.extSettingsAction, el.dataset.ext, fd);
}
</script>
<div class="tr">
    <div class="th"> {$e('backup.h_make')}
        <div class="descr">{$e('backup.d_make', 'db')}</div>
    </div>
    <div class="td">
        <button type=button data-ext-settings-action="post:makeBackup" data-ext="$ext"> {$e('backup.make')} </button> <br>
        <br> $lastBackup &nbsp;
        <button type=button data-ext-settings-action="post:download" data-ext="$ext" $downloadDisabled> {$e('backup.download')} </button>
    </div>
</div>
<div class="tr">
    <div class="th"> {$e('backup.h_inconsistency')}
        <div class="descr">{$e('backup.d_inconsistency')}</div>
    </div>
    <div class="td">
        <button type=button data-ext-settings-action="post:checkInconsistency" data-ext="$ext"> {$e('backup.check')} </button> &nbsp;
        <button type=button data-ext-settings-action="post:repairInconsistency" data-ext="$ext"> {$e('backup.repair')} </button> <br>
    </div>
</div>
<div class="tr">
    <div class="th"> {$e('backup.h_restore')}
        <div class="descr"> {$e('backup.d_restore')} </div>
    </div>
    <div class="td">
        <label class="mtt-settings-upload-button">
            <input type="file" name="file" onchange="return onBackupFileChange(this)" data-ext-settings-action="post:restore" data-ext="$ext">
            {$e('backup.restore')}
        </label>
    </div>
</div>
EOD;
    }

    function settingsPageType(): int
    {
        return 1; //no form buttons
    }

    function saveSettings(array $params, ?string &$outMessage): bool
    {
        return false;
    }
/*
    static function preferences(): array
    {
        return [
            'backupFilePath' => MTTPATH. 'db/backup.xml'
        ];
    }
*/
    static function backupFilePath()
    {
        //return self::preferences()['backupFilePath'];
        return  MTTPATH. 'db/backup.xml';
    }


}
