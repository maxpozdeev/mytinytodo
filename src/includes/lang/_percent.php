#!/usr/bin/env php
<?php

if (php_sapi_name() != 'cli') {
    die("Command-line only!\n");
}

$only = [];
while ($arg = next($argv))
{
    if ($arg == '-v') {
        define("P_VERBOSE", 1);
    }
    else if ($arg[0] == '-') {
        die("Unexpected argument: $arg\n");
    }
    else {
        $only[] = $arg;
    }
}

if (!file_exists("en.json")) {
    die("File not found: en.json\n");
}
$src = json_decode(file_get_contents("en.json"), true) ?? [];
unset($src['_header']);


$totalKeys = checkArray("en.json", $src, $src); //hack
$langs = [];
$files = array_diff(scandir('.') ?? [], ['.', '..', 'en-rtl.json']);
foreach ($files as $file) {
    if (!preg_match("/(.+)\.json$/", $file, $m)) {
        continue; // Skip non-json files
    }
    if (count($only) && !in_array($file, $only)) {
        continue;
    }
    $translated = checkLang($src, $file);
    $langs[$m[1]] = $translated;
}
ksort($langs);

$rows = [];
$rows[] = ["Locale", "Lines", "% Done"];
foreach ($langs as $lang => $translated) {
    $rows[] = [$lang, "$translated/$totalKeys", round(100 * $translated/$totalKeys)."%"];
}

#calc column width
$width = [0,0,0];
foreach ($rows as $row) {
    $width[0] = max($width[0], strlen($row[0]));
    $width[1] = max($width[1], strlen($row[1]));
    $width[2] = max($width[2], strlen($row[2]));
}

# print table
print "# myTinyTodo Translations\n\n";
foreach ($rows as $i => $row) {
    if ($i == 0) {
        print("| ". str_pad($row[0], $width[0], " ", STR_PAD_BOTH). " | ".
            str_pad($row[1], $width[1], " ", STR_PAD_BOTH). " | ".
            str_pad($row[2], $width[2], " ", STR_PAD_BOTH). " |\n");
        print("|:". str_repeat("-", $width[0]). "-|-". str_repeat("-", $width[1]). ":|-". str_repeat("-", $width[2]). ":|\n");
    }
    else {
        print("| ". str_pad($row[0], $width[0], " ", STR_PAD_RIGHT). " | ".
            str_pad($row[1], $width[1], " ", STR_PAD_LEFT). " | ".
            str_pad($row[2], $width[2], " ", STR_PAD_LEFT). " |\n");
    }
}



function checkLang(array $src, string $file) : int
{
    $lang = json_decode(file_get_contents($file), true) ?? [];
    unset($lang['_header']);
    $translated = checkArray($file, $src, $lang);
    return $translated;
}

function checkArray(string $file, array $src, ?array $a) : int
{
    $translated = 0;
    foreach ($src as $k => $v) {
        if (!isset($a[$k])) {
            if (defined('P_VERBOSE')) {
                fwrite(STDERR, "$file: key `$k` is not defined\n");
            }
            continue;
        }
        if (!is_array($v)) {
            ++$translated;
        }
        else if (is_array($a[$k])) {
            ++$translated;
            $translated += checkArray($file, $v, $a[$k]);
        }
        else if (defined('P_VERBOSE')) {
            fwrite(STDERR, "$file: key `$k` is not array\n");
        }
    }
    return $translated;
}




