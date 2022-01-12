<?php

// Rename it to config.php before using in docker.
// On install it will be overwritten.

$config = array();

if (getenv('MTT_DB_TYPE') == 'mysql') {
	$config['db'] = 'mysql';
	$config['mysql.host'] = getenv('MTT_DB_HOST');
	$config['mysql.db'] = getenv('MTT_DB');
	$config['mysql.user'] = getenv('MTT_DB_USER');
	$config['mysql.password'] = getenv('MTT_DB_PASSWORD');
	$config['prefix'] = 'mtt_';
	$config['mysqli'] = 0;
}
else if (getenv('MTT_DB_TYPE') == 'mysqli') {
	$config['db'] = 'mysql';
	$config['mysql.host'] = getenv('MTT_DB_HOST');
	$config['mysql.db'] = getenv('MTT_DB');
	$config['mysql.user'] = getenv('MTT_DB_USER');
	$config['mysql.password'] = getenv('MTT_DB_PASSWORD');
	$config['prefix'] = 'mtt_';
	$config['mysqli'] = 1;
}
else if (getenv('MTT_DB_TYPE') == 'sqlite') {
	$config['db'] = 'sqlite';
	$config['prefix'] = 'mtt_';
}

?>