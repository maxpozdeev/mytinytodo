<?php

// Config for testing (sqlite).
// Override via environment variables: MTT_SALT, MTT_USE_REWRITE, MTT_DEBUG

function env_get(string $key, $default = null)
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

define('MTT_DB_TYPE', 'sqlite');
define('MTT_DB_PREFIX', '');
define('MTT_SALT', env_get('MTT_SALT', 'TestSaltForUnitTests1234567890'));
define('MTT_USE_REWRITE', env_get('MTT_USE_REWRITE', '0') !== '0' && env_get('MTT_USE_REWRITE', '0') !== '');
define('MTT_DEBUG', env_get('MTT_DEBUG', '1') !== '0' && env_get('MTT_DEBUG', '1') !== '');
