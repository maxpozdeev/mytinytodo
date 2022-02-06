#!/usr/bin/env php
<?php

// PHP 5.4 is required

if ( !isset($argv) || !isset($argv[1]) ) {
	die("Usage: buildzip.php <path_to_repo> [-o source.zip] [-v VERSION]\n");
}

$repo = $argv[1];
$dir = sys_get_temp_dir(). DIRECTORY_SEPARATOR. "mytinytodo.build"; #php 5.2.1
$curdir = getcwd();
$zipfile = $curdir. DIRECTORY_SEPARATOR. 'mytinytodo-v@VERSION-@REV.zip';
$ver = 0;

while ($arg = next($argv))
{
	if ($arg == '-o') {
		$zipfile = next($argv);
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
if (!$ver)
{
	chdir($dir);
	$fh = fopen('version.txt', 'r') or die("Cant open version.txt\n");
	$ver = trim(fgets($fh, 100));
	fclose($fh);
}
chdir($dir. DIRECTORY_SEPARATOR. 'src');
$rev = trim(`git show --format=format:%H --summary`);
$rev = substr($rev, 0, 8);
##$ver = str_replace('@REV', $rev, $ver);
print "> Version is $ver\n";

rename('db/todolist.db.empty', 'db/todolist.db');

/*
$fh = fopen("./content/themes/default/index.php", 'a') or die("cant write index.php\n");
fwrite($fh, "\n<!-- $rev -->");
fclose($fh);
*/

#replace @VERSION
replaceVer('./setup.php', $ver);
replaceVer('./init.php', $ver);

unlink('./docker-config.php');
unlink('./content/lang/en-rtl.json');
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

`zip -9 -r mytinytodo.zip mytinytodo`;	#OS dep.!!!
if (!file_exists('mytinytodo.zip')) {
	die("Failed to pack files (no output zip file)\n");
}

$zipfile = str_replace('@VERSION', $ver, $zipfile);
$zipfile = str_replace('@REV', $rev, $zipfile);

chdir($curdir);
if ( ! rename("$dir/mytinytodo.zip", $zipfile) ) {
	die("Failed to move mytinytodo.zip to $zipfile");
}

deleteTreeIfDir($dir);
echo("> Temp dir was cleaned\n");

echo("> Build is stored in $zipfile\n");






function deleteTreeIfDir($dir)
{
	if ( is_dir($dir) ) {
		switch (PHP_OS) {
			case 'Darwin':
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

function replaceVer($filename, $ver)
{
	$s = @file_get_contents($filename) or die("Cant open $filename\n");
	$s = str_replace('@VERSION', $ver, $s);
	@file_put_contents($filename, $s) or die("Cant write $filename\n");
}