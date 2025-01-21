<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2010,2020-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

function htmlarray($a, $exclude=null)
{
    htmlarray_ref($a, $exclude);
    return $a;
}

function htmlarray_ref(&$a, $exclude=null)
{
    if(!$a) return;
    if(!is_array($a)) {
        $a = htmlspecialchars($a);
        return;
    }
    reset($a);
    if($exclude && !is_array($exclude)) $exclude = array($exclude);
    foreach($a as $k=>$v)
    {
        if(is_array($v)) $a[$k] = htmlarray($v, $exclude);
        elseif(!$exclude) $a[$k] = htmlspecialchars($v ?? '');
        elseif(!in_array($k, $exclude)) $a[$k] = htmlspecialchars($v ?? '');
    }
    return;
}

function _post($param,$defvalue = '')
{
    if(!isset($_POST[$param])) {
        return $defvalue;
    }
    else {
        return $_POST[$param];
    }
}

function _get($param,$defvalue = '')
{
    if(!isset($_GET[$param])) {
        return $defvalue;
    }
    else {
        return $_GET[$param];
    }
}

function _server($param, $defvalue = '')
{
    if ( !isset($_SERVER[$param]) ) {
        return $defvalue;
    }
    else {
        return $_SERVER[$param];
    }
}

function formatDate3($format, $ay, $am, $ad, $lang)
{
    # F - month long, M - month short
    # m - month 2-digit, n - month 1-digit
    # d - day 2-digit, j - day 1-digit
    $ml = $lang->get('months_long');
    $ms = $lang->get('months_short');
    $Y = $ay;
    $YC = 100 * floor($Y/100); //...1900,2000,2100...
    if ($YC == 2000) $y = $Y < $YC+10 ? '0'.($Y-$YC) : $Y-$YC;
    else $y = $Y;
    $n = $am;
    $m = $n < 10 ? '0'.$n : $n;
    $F = $ml[$am-1];
    $M = $ms[$am-1];
    $j = $ad;
    $d = $j < 10 ? '0'.$j : $j;
    return strtr($format, array('Y'=>$Y, 'y'=>$y, 'F'=>$F, 'M'=>$M, 'n'=>$n, 'm'=>$m, 'd'=>$d, 'j'=>$j));
}

function daysInMonth(int $m, int $y = 0): int
{
    if ($y == 0) $y = (int)date('Y');
    $isLeap = (0 == $y % 4) && ((0 != $y % 100) || (0 == $y % 400));
    $a = array(1=>31, ($isLeap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    if (isset($a[$m])) return $a[$m];
    else return 0;
}


function getRequestUri()
{
    // Do not use HTTP_X_REWRITE_URL due to CVE-2018-14773
    // SCRIPT_NAME or PATH_INFO ?
    if (isset($_SERVER['SCRIPT_NAME'])) {
        return $_SERVER['SCRIPT_NAME'];
    }
    elseif (isset($_SERVER['REQUEST_URI'])) {
        return $_SERVER['REQUEST_URI'];
    }
    else if (isset($_SERVER['ORIG_PATH_INFO']))  // IIS 5.0 CGI
    {
        $uri = $_SERVER['ORIG_PATH_INFO']; //has no query
        if (!empty($_SERVER['QUERY_STRING'])) $uri .= '?'. $_SERVER['QUERY_STRING'];
        return $uri;
    }
}

function url_dir($url, $onlyPath = 1)
{
    if (false !== $p = strpos($url, '?')) {
        $url = substr($url, 0, $p); # to avoid parse errors on strange query strings
    }
    if ($onlyPath) {
        $url = parse_url($url, PHP_URL_PATH);
    }
    if ($url == '') {
        return '/';
    }
    if (substr($url, -1) == '/') {
        return $url;
    }
    if (false !== $p = strrpos($url, '/')) {
        return substr($url, 0, $p+1);
    }
    return '/';
}

function removeNewLines($s)
{
    return str_replace( ["\r","\n"], '', $s );
}

/**
 * Generates UUID v4
 * Implementation from https://github.com/symfony/polyfill-uuid
 */
function generateUUID(): string
{
    $uuid = bin2hex(random_bytes(16));
    return sprintf('%08s-%04s-4%03s-%04x-%012s',
        substr($uuid, 0, 8),
        substr($uuid, 8, 4),
        // $uuid[14] = 4
        substr($uuid, 13, 3),
        hexdec(substr($uuid, 16, 4)) & 0x3fff | 0x8000,
        substr($uuid, 20, 12)
    );
}

function passwordHash(string $p): string
{
    if ($p == '') return '';
    return 'sha256:'. hash('sha256', $p);
}

/**
 * Compares raw (not hashed) password with password hash. Return true if equals.
 * @param string $p Raw password
 * @param string $hash Password hash
 * @return bool
 */
function isPasswordEqualsToHash(string $p, string $hash): bool
{
    if ($hash == '' && $p == '') return true;
    if ($hash == '' || $p == '') return false;
    if ( false !== $pos = strpos($hash, ':') ) {
        $algo = substr($hash, 0, $pos);
        if ($algo != 'sha256') throw new Exception("Unsupported algo of password hash");
        if ( hash_equals($hash, passwordHash($p)) ) return true;
    }
    return false;
}

function idSignature(string $id, string $key, string $salt): string
{
    $secret = $key.$salt;
    return hash_hmac('sha256', $id, $secret);
}

function isValidSignature(string $signature, string $id, string $key, string $salt): bool
{
    if ( hash_equals($signature, idSignature($id, $key, $salt)) ) return true;
    return false;
}


function randomString(int $len = 16, string $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') : string
{
    $a = [];
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $len; $i++) {
        $a[]= $chars[random_int(0, $max)];
    }
    return implode('', $a);
}
