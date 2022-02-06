<?php

// Rename it to config.php before using in docker.

if (getenv('MTT_DB_TYPE') == 'mysql') {
    define("MTT_DB_TYPE", "mysql");
    define("MTT_DB_HOST", getenv('MTT_DB_HOST'));
    define("MTT_DB_NAME", getenv('MTT_DB'));
    define("MTT_DB_USER", getenv('MTT_DB_USER'));
    define("MTT_DB_PASSWORD", getenv('MTT_DB_PASSWORD'));
    define("MTT_DB_PREFIX", getenv('MTT_DB_PREFIX'));
    define("MTT_DB_DRIVER", getenv('MTT_DB_DRIVER'));
}
else if (getenv('MTT_DB_TYPE') == 'sqlite') {
    define("MTT_DB_TYPE", "sqlite");
    define("MTT_DB_PREFIX", "");
}

define("MTT_SALT", "Random text");