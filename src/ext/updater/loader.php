<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (!defined('MTTPATH')) {
    die("Unexpected usage.");
}

require_once('class.controller.php');

function mtt_ext_updater_instance(): MTTExtension
{
    return new UpdaterExtension();
}

use UpdaterExtension\Controller;

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
        if (time() - $lastCheck > 86400*7) {
            $a = self::lastVersionInfo();
            if ($a) {
                $lastCheck = $prefs['lastCheck'] = time();
                $version = $prefs['version'] = $a['version'] ?? '';
                $prefs['download'] = $a['download'] ?? '';
                Config::saveDomain(self::domain, $prefs);
            }
        }
        if ($version != '') {
            if ( version_compare($version, mytinytodo\Version::VERSION) > 0 ) {
                $updateStr = "<br> {$e('updater.updatet_version_avaialable')}: ". htmlspecialchars($version);
                if ("1.7." == substr($version, 0, 4)) {
                    $updateStr .= "<br><br>\n <a href=\"#\" data-ext-settings-action=\"post:update\" data-ext=\"$ext\">{$e('updater.update')}</a> ";
                }
            }
            else {
                $updateStr = "<br>{$e('updater.no_updates')}";
            }
        }
        $lastCheckStr = $lastCheck ? timestampToDatetime($lastCheck, true) : "";


        return
<<<EOD
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

    static function lastVersionInfo(): ?array
    {
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json\r\nUser-Agent: mytinytodo\r\n"
            )
        );
        $context  = stream_context_create($options);
        $json = @file_get_contents("https://api.github.com/repos/maxpozdeev/mytinytodo/releases/latest", false, $context);
        if ($json === false || $json == '') {
            return null;
        }
        $a = json_decode($json, true) ?? [];
        $ret = [];
        if ( isset($a['name']) && isset($a['assets']) &&
             is_array($a['assets']) && count($a['assets']) > 0 &&
             ($asset = $a['assets'][0]) && isset($asset['browser_download_url']) )
        {
            $ret['version'] = substr($a['name'], 1); //remove first 'v'
            $ret['download'] = $asset['browser_download_url'];
        }
        else {
            error_log("Unexpected content");
        }
        return $ret;
    }

    static function download(string $url, string $outfile, string &$error = null): bool
    {
        $dir = dirname($outfile);
        if (!is_dir($dir) || !is_writable($dir)) {
            $error = "myTinyTodo directory is not writable";
            return false;
        }
        $f = @fopen($url, 'r');
        if ($f === false) {
            $ea = error_get_last();
            $error = ($ea && isset($ea['message'])) ? $ea['message'] : "Failed to open stream";
            return false;
        }
        $bytes = @file_put_contents($outfile, $f, LOCK_EX);
        $ea = error_get_last();
        fclose($f);
        if ($bytes === false) {
            $error = ($ea && isset($ea['message'])) ? $ea['message'] :  "Can not save file";
            return false;
        }
        return true;
    }

    static function extractAndReplace(string $filename, string &$error = null): bool
    {
        $dir = MTTPATH;
        if (!is_dir($dir) || !is_writable($dir)) {
            $error = "myTinyTodo directory is not writable";
            return false;
        }

        $output = null;
        $retval = null;
        $command = "tar xzf ". escapeshellarg($filename). " --strip-components 1 -C ". escapeshellarg($dir). " 2>&1";
        @exec($command, $output, $retval);
        if ($retval != 0) {
            $error = "Failed to execute tar command ($retval): ". ($output ? implode("\n", $output) : "no output");
            error_log($error);
            return false;
        }

        // Extensions
        $dir = MTT_EXT;
        $filename = $dir . 'extensions.tar.gz';
        if (file_exists($filename)) {
            if (!is_writable($dir)) {
                $error = "Extensions directory is not writable";
                return false;
            }
            $command = "tar xzf ". escapeshellarg($filename). " -C ". escapeshellarg($dir). " 2>&1";
            @exec($command, $output, $retval);
            if ($retval != 0) {
                $error = "Extensions: failed to execute tar command ($retval): ". ($output ? implode("\n", $output) : "no output");
                error_log($error);
                return false;
            }
            unlink($filename);
        }

        return true;
    }


}
