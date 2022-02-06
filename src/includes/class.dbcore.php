<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/


// FIXME: experimental, subject to change

class DBCore
{
    /** @var Database_Abstract $db */
    protected $db;

    /** @var DBCore $defaultdb */
    protected static $defaultInstance;

    /**
     *
     * @param Database_Abstract $db Value of DBConnection::instance() or similar
     * @return void
     */
    public function __construct(Database_Abstract $db) {
        $this->db = $db;
    }

    /**
     *
     * @return Database_Abstract
     * @throws Exception
     */
    public function connection()
    {
        if (!isset($this->db)) {
            throw new Exception("DBConnection is not set");
        }
        return $this->db;
    }

    /**
     *
     * @return DBCore
     * @throws Exception
     */
    public static function defaultInstance() : DBCore
    {
        if (!isset(self::$defaultInstance)) {
            throw new Exception("DBCore defaultInstance is not initialized");
        }
        return self::$defaultInstance;
    }

    /**
     *
     * @param DBCore $instance
     * @return void
     */
    public static function setDefaultInstance(DBCore $instance)
    {
        self::$defaultInstance = $instance;
    }

    /**
     *
     * @param int $id
     * @return int
     */
    public function getListIdByTaskId(int $id): int
    {
        $db = $this->db;
        $listId = (int)$db->sq("SELECT list_id FROM {$db->prefix}todolist WHERE id=". (int)$id);
        return $listId;
    }

}

