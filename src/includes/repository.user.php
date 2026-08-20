<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class UserRepo
{
    protected AbstractDatabase $db;

    function __construct(AbstractDatabase $db)
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

    /**
     * Search user id by its e-mail.
     * Return null if user does not exists.
     * @param string $email
     * @return null|int
     */
    public function findUserIdByEmail(string $email): ?int
    {
        $r = $this->db->sq("SELECT id FROM {$this->db->prefix}users WHERE email=?", [$email]);
        if (is_null($r))
            return null;
        return (int)$r;
    }

    /**
     * Get array with raw user data by its username.
     * Return null if user does not exists.
     * @return null|array
     */
    public function userDataByUsername(string $username): ?array
    {
        $r = $this->db->sqa("SELECT * FROM {$this->db->prefix}users WHERE username=?", [$username]);
        if ($r) {
            return $r;
        }
        return null;
    }


    /**
     * Get array with raw user data by its id.
     * Return null if user does not exists.
     * @return null|array
     */
    public function userDataById(int $id): ?array
    {
        $r = $this->db->sqa("SELECT * FROM {$this->db->prefix}users WHERE id=?", [$id]);
        if ($r) {
            return $r;
        }
        return null;
    }
}
