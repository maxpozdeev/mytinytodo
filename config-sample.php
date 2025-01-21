<?php

/*
  Uncomment the line with MTT_DB_TYPE if you make clean install only.
  Leave it commented (with # at start) if you are upgrading from version before 1.7.
  Select the database type: sqlite or mysql or postgres.
*/

#define("MTT_DB_TYPE", "sqlite");

define("MTT_DB_HOST", "localhost");

define("MTT_DB_NAME", "mytinytodo");

define("MTT_DB_USER", "mtt");

define("MTT_DB_PASSWORD", "mtt");

define("MTT_DB_PREFIX", "");

// set mysqli if needed
define("MTT_DB_DRIVER", "");

define("MTT_SALT", "Put random text here");