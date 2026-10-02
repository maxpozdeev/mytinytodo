<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023,2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

// call like: php -f svg2base64.php > ../images.css

if (php_sapi_name() != 'cli') {
    error_log("Supports cli only");
    exit(-1);
}

if (isset($argv[1])) {
    if (!file_exists($argv[1])) die("File {$argv[1]} does not exists\n");
    print asVar(pathinfo($argv[1], PATHINFO_BASENAME), base64file($argv[1])) . "\n";
    exit();
}

$files = [];
$h = opendir(__DIR__);
while ( false !== ($file = readdir($h)) )
{
    if ( preg_match('/(.+)\.svg$/', $file, $m) ) {
        $files[] = $m[1];
    }
}
closedir($h);

if (!$files) {
    exit;
}
sort($files);

print ":root {\n";
foreach ($files as $name) {
    $b64 = base64file(__DIR__. "/$name.svg");
    print "  ". asVar($name, $b64). "\n";
}
print "}\n";

function base64file(string $filename): string
{
    $content = file_get_contents($filename);
    //$content = str_replace(["\n","\r\n"], ['',''], $content);
    $content = cleanXml($content);
    return base64_encode($content);
}

function cleanXml(string $data): string
{
    $dom = new DOMDocument;
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($data);

    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//comment()') as $comment) {
        $comment->parentNode->removeChild($comment);
    }
    return $dom->saveXML();
}

function asVar(string $name, string $base64)
{
    return "--svg-{$name}: url('data:image/svg+xml;base64,$base64');";
}
