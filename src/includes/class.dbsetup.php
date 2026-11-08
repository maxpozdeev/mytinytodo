<?php

/*
    This file is part of myTinyTodo.
    (C) Copyright 2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

/**
 * Database setup helper class.
 * Contains static methods for creating tables for different database backends.
 */
class MTTDBSetup
{
    /**
     * Check if MySQL supports utf8mb4_unicode_520_ci collation.
     */
    public static function hasMysqlUnicode520(AbstractDatabase $db): bool
    {
        $r = $db->sq("SHOW COLLATION WHERE Charset='utf8mb4' and Collation='utf8mb4_unicode_520_ci'");
        return $r ? true : false;
    }

    /**
     * Create all tables for MySQL database.
     */
    public static function createMysqlTables(AbstractDatabase $db)
    {
        $collation = 'utf8mb4_unicode_520_ci';

        // Mysql does not support transactions while executing DDL
        $db->ex(
"CREATE TABLE {$db->prefix}lists (
    `id` INT UNSIGNED NOT NULL auto_increment,
    `uuid` CHAR(36) CHARACTER SET latin1 NOT NULL default '',
    `ow` INT NOT NULL default 0,
    `name` VARCHAR(250) NOT NULL default '',
    `user_id` INT UNSIGNED NOT NULL default 0,
    `d_created` BIGINT UNSIGNED NOT NULL default 0,
    `d_edited` BIGINT UNSIGNED NOT NULL default 0,
    `sorting` TINYINT UNSIGNED NOT NULL default 0,
    `published` TINYINT UNSIGNED NOT NULL default 0,
    `taskview` INT UNSIGNED NOT NULL default 0,
    `extra` TEXT default NULL,
    PRIMARY KEY(`id`),
    UNIQUE KEY(`uuid`),
    KEY(`user_id`)
) CHARSET=utf8mb4 COLLATE $collation ");


        $db->ex(
"CREATE TABLE {$db->prefix}todolist (
    `id` INT UNSIGNED NOT NULL auto_increment,
    `uuid` CHAR(36) CHARACTER SET latin1 NOT NULL default '',
    `list_id` INT UNSIGNED NOT NULL default 0,
    `parent_id` INT UNSIGNED NOT NULL default 0,
    `d_created` BIGINT UNSIGNED NOT NULL default 0,   /* time() timestamp */
    `d_completed` BIGINT UNSIGNED NOT NULL default 0, /* time() timestamp */
    `d_edited` BIGINT UNSIGNED NOT NULL default 0,    /* time() timestamp */
    `compl` TINYINT UNSIGNED NOT NULL default 0,
    `title` VARCHAR(250) NOT NULL,
    `note` MEDIUMTEXT default NULL,
    `prio` TINYINT NOT NULL default 0,          /* priority -,0,+ */
    `ow` INT NOT NULL default 0,                /* order weight */
    `duedate` DATE default NULL,
    `extra` TEXT default NULL,
    PRIMARY KEY(`id`),
    KEY(`list_id`),
    UNIQUE KEY(`uuid`)
) CHARSET=utf8mb4 COLLATE $collation ");

        // Max length of varchar of utf8mb4 with UNIQUE index is 191 until Mysql 5.7.9 and MariaDB 10.2.2
        // since then innodb_default_row_format = DYNAMIC, https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-9.html
        $db->ex(
"CREATE TABLE {$db->prefix}tags (
    `id` INT UNSIGNED NOT NULL auto_increment,
    `user_id` INT UNSIGNED NOT NULL default 0,
    `name` VARCHAR(250) NOT NULL default '',
    PRIMARY KEY(`id`),
    UNIQUE KEY `name` (`name`),
    KEY(`user_id`)
) CHARSET=utf8mb4 COLLATE $collation ");


        $db->ex(
"CREATE TABLE {$db->prefix}tag2task (
    `tag_id` INT UNSIGNED NOT NULL,
    `task_id` INT UNSIGNED NOT NULL,
    `list_id` INT UNSIGNED NOT NULL,
    KEY(`tag_id`),
    KEY(`task_id`),
    KEY(`list_id`)  /* for tagcloud */
) CHARSET=utf8mb4 COLLATE $collation ");


        $db->ex(
"CREATE TABLE {$db->prefix}users (
    `id` INT UNSIGNED NOT NULL auto_increment,
    `username` VARCHAR(250) NOT NULL default '',
    `email` VARCHAR(250) NOT NULL default '',
    `name` VARCHAR(250) NOT NULL default '',
    `pwhash` VARCHAR(250) NOT NULL default '',
    `pwtoken` VARCHAR(250) NOT NULL default '',
    `last_visit` DATE default NULL,
    `extra` TEXT default NULL,
    PRIMARY KEY(`id`),
    UNIQUE KEY (`username`),
    UNIQUE KEY (`email`)
) CHARSET=utf8mb4 COLLATE $collation ");


        $db->ex(
"CREATE TABLE {$db->prefix}usersettings (
    `user_id` INT UNSIGNED NOT NULL default 0,
    `param_key` VARCHAR(250) NOT NULL default '',
    `param_value` TEXT default NULL,
    UNIQUE KEY (`user_id`, `param_key`)
) CHARSET=utf8mb4 COLLATE $collation ");


        $db->ex(
"CREATE TABLE {$db->prefix}settings (
    `param_key`   VARCHAR(250) CHARACTER SET latin1 NOT NULL default '',
    `param_value` TEXT,
    UNIQUE KEY `param_key` (`param_key`)
) CHARSET=utf8mb4 COLLATE $collation ");


        $db->ex(
"CREATE TABLE {$db->prefix}sessions (
    `id`          VARCHAR(64) CHARACTER SET latin1 NOT NULL default '',  /* upto 64 bytes for sha256 */
    `data`        TEXT,
    `last_access` BIGINT UNSIGNED NOT NULL default 0,  /* time() timestamp */
    `expires`     BIGINT UNSIGNED NOT NULL default 0,  /* time() timestamp */
    UNIQUE KEY `id` (`id`)
) CHARSET=utf8mb4 COLLATE $collation ");
    }



    /* ===== postgres ========================================================= */

    public static function createPostgresTables(AbstractDatabase $db)
    {
        $db->ex(
"CREATE TABLE {$db->prefix}lists (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    uuid CHAR(36) NOT NULL default '',
    ow INTEGER NOT NULL default 0,
    name VARCHAR(250) NOT NULL default '',
    user_id INTEGER NOT NULL default 0,
    d_created BIGINT NOT NULL default 0,
    d_edited BIGINT NOT NULL default 0,
    sorting SMALLINT NOT NULL default 0,
    published SMALLINT NOT NULL default 0,
    taskview INTEGER NOT NULL default 0,
    extra TEXT
) ");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}lists_uuid ON {$db->prefix}lists (uuid)");
        $db->ex("CREATE INDEX {$db->prefix}lists_user_id ON {$db->prefix}lists (user_id)");

        $db->ex(
"CREATE TABLE {$db->prefix}todolist (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    uuid CHAR(36) NOT NULL default '',
    list_id INTEGER NOT NULL default 0,
    parent_id INTEGER NOT NULL default 0,
    d_created BIGINT NOT NULL default 0,
    d_completed BIGINT NOT NULL default 0,
    d_edited BIGINT NOT NULL default 0,
    compl SMALLINT NOT NULL default 0,
    title VARCHAR(250) NOT NULL default '',
    note TEXT default NULL,
    prio SMALLINT NOT NULL default 0,
    ow INTEGER NOT NULL default 0,
    duedate DATE default NULL,
    extra TEXT default NULL
) ");
        $db->ex("CREATE INDEX {$db->prefix}todo_list_id ON {$db->prefix}todolist (list_id)");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}todo_uuid ON {$db->prefix}todolist (uuid)");

        $db->ex(
"CREATE TABLE {$db->prefix}tags (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    user_id INTEGER NOT NULL default 0,
    name VARCHAR(250) NOT NULL DEFAULT ''
) ");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}tags_lower_name ON {$db->prefix}tags ((LOWER(name)))");
        $db->ex("CREATE INDEX {$db->prefix}tags_user_id ON {$db->prefix}tags (user_id)");

        $db->ex(
"CREATE TABLE {$db->prefix}tag2task (
    tag_id INTEGER NOT NULL,
    task_id INTEGER NOT NULL,
    list_id INTEGER NOT NULL
) ");
        $db->ex("CREATE INDEX {$db->prefix}tag2task_tag_id ON {$db->prefix}tag2task (tag_id)");
        $db->ex("CREATE INDEX {$db->prefix}tag2task_task_id ON {$db->prefix}tag2task (task_id)");
        $db->ex("CREATE INDEX {$db->prefix}tag2task_list_id ON {$db->prefix}tag2task (list_id)");


        $db->ex(
"CREATE TABLE {$db->prefix}users (
    id INTEGER NOT NULL GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    username VARCHAR(250) NOT NULL DEFAULT '',
    email VARCHAR(250) NOT NULL DEFAULT '',
    name VARCHAR(250) NOT NULL DEFAULT '',
    pwhash VARCHAR(250) NOT NULL DEFAULT '',
    pwtoken VARCHAR(250) NOT NULL DEFAULT '',
    last_visit DATE default NULL,
    extra TEXT default NULL
) ");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}users_lower_username ON {$db->prefix}users ((LOWER(username)))");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}users_lower_email ON {$db->prefix}users ((LOWER(email)))");


        $db->ex(
"CREATE TABLE {$db->prefix}usersettings (
    user_id INTEGER NOT NULL default 0,
    param_key VARCHAR(250) NOT NULL default '',
    param_value TEXT default NULL
) ");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}usersettings_ukey ON {$db->prefix}usersettings (user_id, param_key)");


        $db->ex(
"CREATE TABLE {$db->prefix}settings (
    param_key   VARCHAR(250) NOT NULL default '',
    param_value TEXT
) ");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}settings_key ON {$db->prefix}settings (param_key)");


        $db->ex(
"CREATE TABLE {$db->prefix}sessions (
    id          VARCHAR(64) NOT NULL default '',
    data        TEXT,
    last_access BIGINT NOT NULL default 0,
    expires     BIGINT NOT NULL default 0
) ");
        $db->ex("CREATE UNIQUE INDEX {$db->prefix}sessions_id ON {$db->prefix}sessions (id)");
    }



    /* ===== sqlite ============================================================ */

    public static function createSqliteTables(AbstractDatabase $db)
    {
        $db->ex(
"CREATE TABLE {$db->prefix}lists (
    id INTEGER PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    ow INTEGER NOT NULL default 0,
    name VARCHAR(250) NOT NULL,
    user_id INTEGER UNSIGNED NOT NULL default 0,
    d_created INTEGER UNSIGNED NOT NULL default 0,
    d_edited INTEGER UNSIGNED NOT NULL default 0,
    sorting TINYINT UNSIGNED NOT NULL default 0,
    published TINYINT UNSIGNED NOT NULL default 0,
    taskview INTEGER UNSIGNED NOT NULL default 0,
    extra TEXT
) ");

        $db->ex("CREATE UNIQUE INDEX lists_uuid ON {$db->prefix}lists (uuid)");
        $db->ex("CREATE INDEX lists_user_id ON {$db->prefix}lists (user_id)");

        $db->ex(
"CREATE TABLE {$db->prefix}todolist (
    id INTEGER PRIMARY KEY,
    uuid CHAR(36) NOT NULL default '',
    list_id INTEGER UNSIGNED NOT NULL default 0,
    parent_id INTEGER UNSIGNED NOT NULL default 0,
    d_created INTEGER UNSIGNED NOT NULL default 0,
    d_completed INTEGER UNSIGNED NOT NULL default 0,
    d_edited INTEGER UNSIGNED NOT NULL default 0,
    compl TINYINT UNSIGNED NOT NULL default 0,
    title VARCHAR(250) NOT NULL default '',
    note TEXT default NULL,
    prio TINYINT NOT NULL default 0,
    ow INTEGER NOT NULL default 0,
    duedate DATE default NULL,
    extra TEXT default NULL
) ");
        $db->ex("CREATE INDEX todo_list_id ON {$db->prefix}todolist (list_id)");
        $db->ex("CREATE UNIQUE INDEX todo_uuid ON {$db->prefix}todolist (uuid)");


        $db->ex(
"CREATE TABLE {$db->prefix}tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNSIGNED NOT NULL default 0,
    name VARCHAR(250) NOT NULL DEFAULT ''
) ");
        $db->ex("CREATE INDEX tags_user_id ON {$db->prefix}tags (user_id)");


        $db->ex(
"CREATE TABLE {$db->prefix}tag2task (
    tag_id INTEGER NOT NULL,
    task_id INTEGER NOT NULL,
    list_id INTEGER NOT NULL
) ");
        $db->ex("CREATE INDEX tag2task_tag_id ON {$db->prefix}tag2task (tag_id)");
        $db->ex("CREATE INDEX tag2task_task_id ON {$db->prefix}tag2task (task_id)");
        $db->ex("CREATE INDEX tag2task_list_id ON {$db->prefix}tag2task (list_id)");    /* for tagcloud */


        $db->ex(
            "CREATE TABLE {$db->prefix}users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   VARCHAR(250) NOT NULL DEFAULT '',
            email      VARCHAR(250) NOT NULL DEFAULT '',
            name       VARCHAR(250) NOT NULL DEFAULT '',
            pwhash     VARCHAR(250) NOT NULL DEFAULT '',
            pwtoken    VARCHAR(250) NOT NULL DEFAULT '',
            last_visit DATE default NULL,
            extra      TEXT default NULL
        ) ");
        $db->ex("CREATE UNIQUE INDEX users_username ON {$db->prefix}users (username COLLATE NOCASE)");
        $db->ex("CREATE UNIQUE INDEX users_email ON {$db->prefix}users (email COLLATE NOCASE)");


        $db->ex(
            "CREATE TABLE {$db->prefix}usersettings (
            user_id     INTEGER UNSIGNED NOT NULL default 0,
            param_key   VARCHAR(250) NOT NULL default '',
            param_value TEXT
        ) ");
        $db->ex("CREATE UNIQUE INDEX usersettings_ukey ON {$db->prefix}usersettings (user_id, param_key COLLATE NOCASE)");


        $db->ex(
"CREATE TABLE {$db->prefix}settings (
    param_key   VARCHAR(250) NOT NULL default '',
    param_value TEXT
) ");
        $db->ex("CREATE UNIQUE INDEX settings_key ON {$db->prefix}settings (param_key COLLATE NOCASE)");


        $db->ex(
"CREATE TABLE {$db->prefix}sessions (
    id          VARCHAR(64) NOT NULL default '',
    data        TEXT,
    last_access INTEGER UNSIGNED NOT NULL default 0,
    expires     INTEGER UNSIGNED NOT NULL default 0
) ");

        $db->ex("CREATE UNIQUE INDEX sessions_id ON {$db->prefix}sessions (id COLLATE NOCASE)");
    }
}
