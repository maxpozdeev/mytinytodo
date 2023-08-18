<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace UpdaterExtension;

class Updater
{
    public $lastErrorString = null;

    public function lastVersionInfo(): ?array
    {
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json\r\nUser-Agent: mytinytodo\r\n"
            )
        );
        $context  = stream_context_create($options);
        set_error_handler(function ($errno, $message, $file, $line) {
            throw new \ErrorException($message, $errno, $errno, $file, $line);
        });
        $json = null;
        $this->lastErrorString = null;
        try {
            $json = @file_get_contents("https://api.github.com/repos/maxpozdeev/mytinytodo/releases/latest", false, $context);
        }
        catch (\Exception $e) {
            $this->lastErrorString = boolval(ini_get('html_errors')) ?  htmlspecialchars_decode($e->getMessage()) : $e->getMessage();
        }
        restore_error_handler();
        if ($json === false || $json == '') {
            error_log("Failed to get last version info: ".$this->lastErrorString);
            return null;
        }
        $a = json_decode($json, true) ?? [];
        $ret = [];
        $ver = '';
        if (isset($a['tag_name'])) {
            $ver = substr($a['tag_name'], 1); //remove first 'v'
        }
        if ($ver != '' && isset($a['assets']) &&
             is_array($a['assets']) && count($a['assets']) > 0 &&
             ($asset = $a['assets'][0]) && isset($asset['browser_download_url']) )
        {
            $ret['version'] = $ver;
            $ret['download'] = $asset['browser_download_url'];
        }
        else {
            error_log("HTTP response contains unexpected content");
            $this->lastErrorString = "HTTP response contains unexpected content";
        }
        return $ret;
    }

    public function download(string $url, string $outfile): bool
    {
        $this->lastErrorString = null;
        $dir = dirname($outfile);
        if (!is_dir($dir) || !is_writable($dir)) {
            $this->lastErrorString = "myTinyTodo directory is not writable";
            return false;
        }
        $f = @fopen($url, 'r');
        if ($f === false) {
            $ea = error_get_last();
            $this->lastErrorString = $ea['message'] ?? "Failed to open stream";
            return false;
        }
        $bytes = @file_put_contents($outfile, $f, LOCK_EX);
        $ea = error_get_last();
        fclose($f);
        if ($bytes === false) {
            $this->lastErrorString = $ea['message'] ??  "Can not save file";
            return false;
        }
        return true;
    }

    public function extractAndReplace(string $filename): bool
    {
        $this->lastErrorString = null;
        $dir = MTTPATH;
        if (!is_dir($dir) || !is_writable($dir)) {
            $this->lastErrorString = "myTinyTodo directory is not writable";
            return false;
        }

        $output = null;
        $retval = null;
        $command = "tar xzf ". escapeshellarg($filename). " --strip-components 1 -C ". escapeshellarg($dir). " 2>&1";
        @exec($command, $output, $retval);
        if ($retval != 0) {
            $this->lastErrorString = "Failed to execute tar command ($retval): ". ($output ? implode("\n", $output) : "no output");
            error_log($this->lastErrorString);
            return false;
        }

        // Extensions
        $dir = MTT_EXT;
        $filename = $dir . 'extensions.tar.gz';
        if (file_exists($filename)) {
            if (!is_writable($dir)) {
                $this->lastErrorString = "Extensions directory is not writable";
                return false;
            }
            $command = "tar xzf ". escapeshellarg($filename). " -C ". escapeshellarg($dir). " 2>&1";
            @exec($command, $output, $retval);
            if ($retval != 0) {
                $this->lastErrorString = "Extensions: failed to execute tar command ($retval): ". ($output ? implode("\n", $output) : "no output");
                error_log($this->lastErrorString);
                return false;
            }
            unlink($filename);
        }

        return true;
    }
}
