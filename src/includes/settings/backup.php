<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

require_once(MTTINC. 'api/BackupController.php');
use Backup\BackupController;

$e = function($s, $arg=null) { return __($s, true, $arg); };

//$filename = MTTPATH. 'db/backup.xml';
$filename = BackupController::backupFilePath();
$downloadDisabled = '';
$lastBackup = '';
if (file_exists($filename)) {
    $time = filemtime($filename);
    $lastBackup = htmlspecialchars( sprintf($e('backup.last_backup'), formatTime(Config::get('dateformat'). " H:i:s", $time)) );
}
else {
    $downloadDisabled = 'disabled';
}

?>

<script>
function onBackupFileChange(el) {
    const fd = new FormData();
    fd.append('file', el.files[0]);
    mtt.cpAction(el.dataset.cpAction, fd);
}
</script>

<h4> <?php _e('set_backup');?> </h4>

<div class="mtt-settings-table">

<div class="tr">
    <div class="th"> <?php echo $e('backup.h_make'); ?>
        <div class="descr"><?php echo $e('backup.d_make', 'db'); ?></div>
    </div>
    <div class="td">
        <button type=button data-cp-action="backup/makeBackup"> <?php echo $e('backup.make'); ?> </button> <br>
        <br> <?php echo $lastBackup; ?>
        <button type=button data-cp-action="backup/download" <?php echo $downloadDisabled; ?>> <?php echo $e('backup.download'); ?> </button>
    </div>
</div>
<div class="tr">
    <div class="th"> <?php echo $e('backup.h_inconsistency'); ?>
        <div class="descr"><?php echo $e('backup.d_inconsistency'); ?></div>
    </div>
    <div class="td">
        <button type=button data-cp-action="backup/checkInconsistency"> <?php echo $e('backup.check'); ?> </button> &nbsp;
        <button type=button data-cp-action="backup/repairInconsistency"> <?php echo $e('backup.repair'); ?> </button> <br>
    </div>
</div>
<div class="tr">
    <div class="th"> <?php echo $e('backup.h_restore'); ?>
        <div class="descr"> <?php echo $e('backup.d_restore'); ?> </div>
    </div>
    <div class="td">
        <button type=button data-cp-action="backup/restoreLocal"> <?php echo $e('backup.restore_local'); ?> </button>
        &nbsp;
        <label class="mtt-settings-upload-button">
            <input type="file" name="file" onchange="return onBackupFileChange(this)" data-cp-action="backup/restore">
            <?php echo $e('backup.restore_upload'); ?>
        </label>
    </div>
</div>

</div>
