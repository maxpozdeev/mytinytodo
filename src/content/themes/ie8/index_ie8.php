<?php

require_once('./init.php');

Config::set('template', 'ie8');

$url = url_dir( get_unsafe_mttinfo('url'), 0 ) . "index_ie8.php";
Config::set('url', $url);
reset_mttinfo('url');

require('./index.php');

