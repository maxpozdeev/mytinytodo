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

unlink('./docker-config.php');
unlink('./includes/lang/en-rtl.json');
unlink('./mtt-edit-settings.php');

chdir('..'); # to the root of repo

assert( strpos(getcwd(), ':') === false ); # FIXME: if path contains a colon ':'
echo("> Run Composer\n");
$retval = 0;
if (false === system( "./composer.sh install --no-dev --no-interaction --optimize-autoloader", $retval) || $retval != 0) {
    die("Failed to install composer libs via docker\n");
}

# ext
if (is_dir('src/ext')) {
    mkdir('src/ext2');
    chdir('src/ext');
    deleteTreeIfDir('_examples');
    $extCount = 0;
    $exts = array_diff(scandir('.') ?? [], ['.', '..']);
    foreach ($exts as $ext) {
        if (is_dir($ext)) {
            rename($ext, "../ext2/$ext");
            $extCount++;
        }
    }
    chdir('../ext2');
    if ($extCount > 0) {
        `tar -czf ../ext/extensions.tar.gz *`;  #OS dep.!!!
    }
    chdir('../..');
    deleteTreeIfDir('src/ext2');
    echo("> Extensions were packed\n");
}


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
    if ( !is_dir($dir) ) {
        return;
    }
    switch (PHP_OS) {
        case 'Darwin':
        case 'Linux':
            system("rm -rf ". escapeshellarg($dir));
            break;
        case 'Windows':
            system("rmdir /s /q ". escapeshellarg($dir));
            break;
        default:
            die("Unknown system ". PHP_OS. "\n");
    }
}
