<?php

require_once('./db/config.php');
require_once('./lang/class.default.php');
require_once('./lang/'. $config['lang']. '.php');

$l = new Lang();

header('Content-type: text/javascript');
echo $l->makeJS();

?>