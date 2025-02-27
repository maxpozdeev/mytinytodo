<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2021-2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class MTTSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    /**
     * @var Database_Abstract
     */
    private $db;

    private $isEmptyData = false;

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

    /**
     * @return bool
     */
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
        $r = $this->db->sq("SELECT data,last_access,expires FROM {$this->db->prefix}sessions WHERE id = ?", [$id]);
        if ( is_null($r) ) {
            // We return '' instead of false to avoid warning
            return '';
        }
        if ( (int)$r[2] < $time) {
            // maybe regenerate id?
            $r[0] = '';
        }

        // update last access time and set expires in 14 days
        // refresh every 8 hours
        if ( $r[1] + 28800 < $time ) {
            $expire = $time + 14 * 86400;
            $this->db->ex("UPDATE {$this->db->prefix}sessions SET last_access=?,expires=? WHERE id = ?",
                array($time, $expire, $id) );
        }

        if ($r[0] === '') {
            $this->isEmptyData = true;
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
        // Ignore empty sessions without changes
        if ($this->isEmptyData && $data === '')
            return true;

        $time = time();
        $expire = $time + 14 * 86400;

        $exists = $this->db->sq("SELECT COUNT(*) FROM {$this->db->prefix}sessions WHERE id = ?", [$id]);
        if (!$exists) {
            // Create new session with 14 days lifetime
            $this->db->ex("INSERT INTO {$this->db->prefix}sessions (id,data,last_access,expires) VALUES (?,?,?,?)",
                array($id, $data, $time, $expire) );
        }
        else {
            // Update existing session
            $this->db->ex("UPDATE {$this->db->prefix}sessions SET data = ?, last_access=?, expires=? WHERE id = ?",
                array($data, $time, $expire, $id) );
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


    /**
     * SessionUpdateTimestampHandlerInterface::validateId
     * @param string $id
     * @return bool
     */
    public function validateId(string $id): bool
    {
        $r = $this->db->sq("SELECT COUNT(*) FROM {$this->db->prefix}sessions WHERE id = ?", [$id]);
        if ($r)
            return true;
        return false;
    }

    /**
     * SessionUpdateTimestampHandlerInterface::updateTimestamp
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function updateTimestamp(string $id, string $data): bool
    {
        // Warning if return false
        return true;
    }
}

