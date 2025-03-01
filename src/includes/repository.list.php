<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class ListRepo
{
    protected Database_Abstract $db;

    function __construct(Database_Abstract $db)
    {
        $this->db = $db;
    }

    /**
     *
     * @param int $userId
     * @return AbstractTasklist[]
     * @throws Exception
     */
    function findListsByUserId(int $userId): array
    {
        $a = [];

        $opts = Config::requestDomain('alltasks.json');
        $a[] = AlltasksList::fromArray($opts);

        $q = $this->db->dq("SELECT * FROM {$this->db->prefix}lists WHERE user_id=? ORDER BY ow ASC, id ASC", [$userId]);
        while ($r = $q->fetchAssoc()) {
            $a[] = TaskList::fromArray($r);
        }
        return $a;
    }

    /**
     *
     * @param int $userId
     * @return TaskList[]
     * @throws Exception
     */
    function findPublicListsByUserId(int $userId): array
    {
        $a = [];
        $q = $this->db->dq("SELECT * FROM {$this->db->prefix}lists WHERE user_id=? AND published=1 ORDER BY ow ASC, id ASC", [$userId]);
        while ($r = $q->fetchAssoc()) {
            $a[] = TaskList::fromArray($r);
        }
        return $a;
    }

    /**
     * Get a list instance by id.
     * Return null if not found.
     * @param int $listId
     * @return null|TaskList
     * @throws Exception
     */
    public function findListById(int $listId): ?TaskList
    {
        $r = $this->db->sqa("SELECT * FROM {$this->db->prefix}lists WHERE id=?", [$listId]);
        return $r ? TaskList::fromArray($r) : null;
    }
}
