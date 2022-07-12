#!/usr/bin/env php
<?php

// PHP 5.4 is required

if ( !isset($argv) || !isset($argv[1]) ) {
    die("Usage: buildtar.php <path_to_repo> [-o source.tar.gz] [-v VERSION]\n");
}

$repo = $argv[1];
$dir = sys_get_temp_dir(). DIRECTORY_SEPARATOR. "mytinytodo.build";
$curdir = getcwd();
$archive = $curdir. DIRECTORY_SEPARATOR. 'mytinytodo-v@VERSION-@REV.tar.gz';
$ver = 0;

while ($arg = next($argv))
{
    if ($arg == '-o') {
        $archive = next($argv);
    }
    elseif ($arg == '-v') {
        $ver = next($argv);
    }
}

deleteTreeIfDir($dir);
$out = `git clone $repo $dir 2>&1`;
if (!is_dir($dir)) {
    die("Error while clone: $out\n");
}
print "> Repository was cloned to temp dir: $dir\n";

#get current version number if not specified
if (!$ver) {
    require_once(__DIR__ . '/src/includes/version.php');
    $ver = mytinytodo\Version::VERSION;
}
chdir($dir. DIRECTORY_SEPARATOR. 'src');
$rev = trim(`git show --format=format:%H --summary`);
$rev = substr($rev, 0, 8);
##$ver = str_replace('@REV', $rev, $ver);
print "> Version is $ver\n";

rename('db/todolist.db.empty', 'db/todolist.db');

unlink('./docker-config.php');
unlink('./includes/lang/en-rtl.json');
unlink('./mtt-edit-settings.php');

/*
# save only 2 languages
$dh = opendir('./content/lang/') or die("Cant opendir lang\n");
while (false !== ($f = readdir($dh))) {
    if (!in_array($f, ['.', '..', '.htaccess', 'en.json', 'ru.json'])) {
        unlink('./content/lang/'. $f);
    }
}
closedir($dh);
 */


chdir('..'); # to the root of repo
rename('src', 'mytinytodo') or die("Cant rename 'src'\n");

`tar -czf mytinytodo.tar.gz mytinytodo`;  #OS dep.!!!
if (!file_exists('mytinytodo.tar.gz')) {
    die("Failed to pack files (no output tar.gz file)\n");
}

$archive = str_replace('@VERSION', $ver, $archive);
$archive = str_replace('@REV', $rev, $archive);

chdir($curdir);
if ( ! rename("$dir/mytinytodo.tar.gz", $archive) ) {
    die("Failed to move mytinytodo.tar.gz to $archive");
}

deleteTreeIfDir($dir);
echo("> Temp dir was cleaned\n");

echo("> Build is stored in $archive\n");






function deleteTreeIfDir($dir)
{
    if ( is_dir($dir) ) {
        switch (PHP_OS) {
            case 'Darwin':
            case 'Linux':
                system("rm -rf $dir");
                break;
            case 'Windows':
                system("rmdir /s /q $dir");
                break;
            default:
                die("Unknown system ". PHP_OS. "\n");
        }
    }
}
