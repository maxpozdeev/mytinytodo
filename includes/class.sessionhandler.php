<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2021-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class MTTSessionHandler implements SessionHandlerInterface
{
    /**
     * @var Database_Abstract
     */
    private $db;

    /**
     * @param string $path
     * @param string $name
     * @return bool
     * @throws Exception
     */
    public function open($path, $name): bool
    {
        $this->db = DBConnection::instance();
        return true;
    }

    /** @return bool  */
    public function close(): bool
    {
        return true;
    }

    /**
     * @param string $id
     * @return string
     * @throws Exception
     */
    #[\ReturnTypeWillChange]
    public function read($id)
    {
        // read session data if not expired
        $time = time();
        $expire = $time;
        $r = $this->db->sq("SELECT data,last_access,expires FROM {$this->db->prefix}sessions WHERE id = ?", [$id]);
        if ( is_null($r) ) return '';
        if ( (int)$r[2] < $time) {
            // maybe regenerate id?
            $r[0] = '';
        }

        // update last access time and set expires in 14 days
        // refresh once in a second
        if ( $r[1] < $time ) {
            $expire = $time + 14 * 86400;
            $this->db->ex("UPDATE {$this->db->prefix}sessions SET last_access=?,expires=? WHERE id = ?",
                array($time, $expire, $id) );
        }
        return $r[0];
    }

    /**
     * @param string $id
     * @param string $data
     * @return bool
     * @throws Exception
     */
    public function write($id, $data): bool
    {
        $exists = $this->db->sq("SELECT COUNT(*) FROM {$this->db->prefix}sessions WHERE id = ?", [$id]);
        if (!$exists) {
            // Create new session with 14 days lifetime
            $expire = time() + 14 * 86400;
            $this->db->ex("INSERT INTO {$this->db->prefix}sessions (id,data,expires) VALUES (?,?,?)",
                array($id, $data, $expire) );
        }
        else {
            // Update existing session
            $this->db->ex("UPDATE {$this->db->prefix}sessions SET data = ? WHERE id = ?",
                array($data, $id) );
        }
        return true;
    }

    /**
     * @param string $id
     * @return bool
     * @throws Exception
     */
    public function destroy($id): bool
    {
        $this->db->ex("DELETE FROM {$this->db->prefix}sessions WHERE id = ?", [$id]);
        return true;
    }

    /**
     * @param int $max_lifetime
     * @return int|false
     */
    #[\ReturnTypeWillChange]
    public function gc($max_lifetime)
    {
        // We ignore php runtime 'session.gc_maxlifetime'
        $expire = time();
        $this->db->ex("DELETE FROM {$this->db->prefix}sessions WHERE expires < $expire");
        return $this->db->affected();
    }
}

