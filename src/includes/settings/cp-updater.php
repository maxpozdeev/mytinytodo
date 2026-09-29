<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

require_once(MTTINC. 'api/UpdaterController.php');
require_once(MTTINC. 'class.updater.php');
use MTTUpdater\UpdaterController;
use MTTUpdater\Updater;

$e = function($s, $arg=null) { return __($s, true, $arg); };

$prefs = UpdaterController::preferences();
$lastCheck = $prefs['lastCheck'] ?? 0;
$version =  $prefs['version'] ?? '';
$updateStr = '';
$curVersion = htmlspecialchars(MTTVersion::VERSION);
$err = null;

// Auto-check on 7+ days since last check
if (time() - $lastCheck > 86400*7) {
    $updater = new Updater;
    $a = $updater->lastVersionInfo();
    if ($a) {
        $lastCheck = $prefs['lastCheck'] = time();
        $version = $prefs['version'] = $a['version'] ?? '';
        $prefs['download'] = $a['download'] ?? '';
        Config::saveDomain(UpdaterController::domain, $prefs);
    }
    else {
        $err = $updater->lastErrorString;
    }
}

$warning = '';
if ($version != '') {
    if ( version_compare($version, MTTVersion::VERSION) > 0 ) {
        $updateStr = "<br> {$e('updater.new_version_available')}: ". htmlspecialchars($version);
        # allow update to v1.7.x and 1.8.x only
        if ( in_array(substr($version, 0, 4), ["1.7.", "1.8."]) ) {
            $updateStr .= "<br><br>\n <button type=button data-cp-action=\"updater/update\">{$e('updater.update')}</button> ";
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
        $updateStr = "<br>{$e('updater.no_updates')}<br>{$e('updater.last_version', $version)}";
    }
}
$lastCheckStr = $err ? $e('updater.download_error') : ($lastCheck ? timestampToDatetime($lastCheck, true) : "");

if (!boolval(ini_get('allow_url_fopen'))) {
    $warning .= "<div class=\"tr\"><div style=\"width:100%;text-align:center;\">⚠️ {$e('updater.urlconfigwarning')}</div></div>";
}

?>

<h4> <?php _e('set_updater');?> </h4>

<div class="mtt-settings-table">

<div class="tr">
    <div class="th"> <?php echo $e('updater.h_check_updates'); ?> </div>
    <div class="td">
       <?php _e('updater.current_version'); ?>: <?php echo $curVersion; ?> <br>
       <?php _e('updater.last_checked'); ?>: <?php echo $lastCheckStr; ?> &nbsp;
        <button type=button data-cp-action="updater/check"><?php _e('updater.check'); ?></button> <br>
       <?php echo $updateStr; ?>
    </div>
</div>

</div>

<?php echo $warning; ?>
