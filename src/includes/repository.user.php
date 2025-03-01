<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class UserRepo
{
    protected Database_Abstract $db;

    function __construct(Database_Abstract $db)
    {
        $this->db = $db;
    }

    /**
     * Search user id by its username.
     * Return null if user does not exists.
     * @param string $username
     * @return null|int
     */
    public function findUserIdByUsername(string $username): ?int
    {
        $r = $this->db->sq("SELECT id FROM {$this->db->prefix}users WHERE username=?", [$username]);
        if (is_null($r))
            return null;
        return (int)$r;
    }
}
