<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace BackupExtension;

use BackupExtension;

class Download
{
    public $filename;
    public $lastErrorString = null;
    private $token = '';

    function __construct(?string $filename)
    {
        $this->filename = is_null($filename) ?  MTTPATH. 'db/backup.xml' : $filename;
    }

    function checkFileAccess(?string $tokenHash = null): bool
    {
        if (!file_exists($this->filename)) {
            $this->lastErrorString = "Backup file not found";
            return false;
        }

        $this->token = access_token();
        if ($this->token == '') {
            $this->lastErrorString = "No token provided";
            return false;
        }

        if (!is_null($tokenHash)) {
            $a = explode(':', $tokenHash, 2);
            $rnd = $a[0] ?? '';
            $hash = $a[1] ?? '';
            if (!hash_equals(hash_hmac('sha256', $rnd, $this->token), $hash)) {
                $this->lastErrorString = "No temp token provided";
                return false;
            }
        }

        return true;
    }

    function downloadUrl()
    {
        $rnd = randomString();
        $hash = $rnd. ':'. hash_hmac('sha256', $rnd, $this->token);
        $url = BackupExtension::extApiActionUrl("download", "t=$hash");
        return $url;
    }

    function printFile()
    {
        header('Content-type: application/xml; charset=utf-8');
        header('Content-disposition: attachment; filename=backup.xml');

        $fh = fopen($this->filename, "r") or die("Couldn't open file");
        if ($fh) {
            while (!feof($fh)) {
                $buffer = fgets($fh, 4096);
                print($buffer);
            }
            fclose($fh);
        }
        exit();
    }

}
