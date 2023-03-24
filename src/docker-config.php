<?php

// Rename it to config.php before using in docker.

if (getenv('MTT_DB_TYPE') == 'mysql') {
    define("MTT_DB_TYPE", "mysql");
    define("MTT_DB_HOST", getenv('MTT_DB_HOST'));
    define("MTT_DB_NAME", getenv('MTT_DB_NAME'));
    define("MTT_DB_USER", getenv('MTT_DB_USER'));
    define("MTT_DB_PASSWORD", getenv('MTT_DB_PASSWORD'));
    define("MTT_DB_PREFIX", getenv('MTT_DB_PREFIX'));
    define("MTT_DB_DRIVER", getenv('MTT_DB_DRIVER'));
}
else if (getenv('MTT_DB_TYPE') == 'sqlite') {
    define("MTT_DB_TYPE", "sqlite");
    define("MTT_DB_PREFIX", "");
}

define("MTT_SALT", "Put Random Text Here");
//define("MTT_DISABLE_EXT", 1);
if (getenv('MTT_API_USE_PATH_INFO')) {
    define("MTT_API_USE_PATH_INFO", 1);
}
