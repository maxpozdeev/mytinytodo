<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

// call like: php -f svg2base64.php > ../images.css

if (php_sapi_name() != 'cli') {
    error_log("Supports cli only");
    exit(-1);
}

if (isset($argv[1])) {
    print  base64file($argv[1]);
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
    print "  --svg-{$name}: url('data:image/svg+xml;base64,$b64');\n";
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
