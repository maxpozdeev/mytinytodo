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


    /**
     * Set order of lists of specfic user
     * @param [int|string] $order Ids of Lists in order of appearance
     * @param int $userId
     * @return void
     * @throws Exception
     */
    public function updateListOrderOfUser(array $order, int $userId)
    {
        $a = array();
        $setCase = '';
        foreach ($order as $ow => $id) {
            $id = (int)$id;
            $a[] = $id;
            $setCase .= "WHEN id=$id THEN $ow\n";
        }
        $ids = implode(',', $a);
        $this->db->dq("UPDATE {$this->db->prefix}lists SET ow = CASE\n $setCase END WHERE id IN ($ids) AND user_id=?",
                    array($userId) );

    }


    /**
     * Delete a list by id of specific user by its id
     * @param int $listId
     * @param int $userId
     * @return int
     */
    public function deleteListOfUser(int $listId, int $userId): int
    {
        $this->db->ex("BEGIN");
        $this->db->ex("DELETE FROM {$this->db->prefix}lists WHERE id=? AND user_id=?", [$listId, $userId]);
        $affected = $this->db->affected();
        if ($affected) {
            $this->db->ex("DELETE FROM {$this->db->prefix}tag2task WHERE list_id=?", [$listId]);
            $this->db->ex("DELETE FROM {$this->db->prefix}todolist WHERE list_id=?", [$listId]);
        }
        $this->db->ex("COMMIT");
        return $affected;
    }
}
