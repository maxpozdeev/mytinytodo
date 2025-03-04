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
        if (!array_is_list($order))
            throw new InvalidArgumentException("order");
        $a = array();
        $setCase = '';
        $max = count($order);
        for ($i = 0; $i < $max; $i++) {
            $id = (int)$order[$i];
            $a[] = $id;
            $ow = $i + 1;
            $setCase .= "WHEN id=$id THEN $ow\n";
        }
        $ids = implode(',', $a);
        $this->db->dq("UPDATE {$this->db->prefix}lists SET ow = CASE\n $setCase END WHERE id IN ($ids) AND user_id=?",
                    array($userId) );

    }


    /**
     * Create new list with name
     * @param string $name
     * @param int $userId
     * @return null|int
     */
    public function createList(string $name, int $userId): ?int
    {
        $name = str_replace( ['"',"'",'<','>','&'], '', trim($name) );
        if ($name == '') {
            return null;
        }
        $time = time();
        $this->db->dq("INSERT INTO {$this->db->prefix}lists (user_id,uuid,name,d_created,d_edited,taskview,ow) VALUES (?,?,?,?,?,?,
            (SELECT 1 + COALESCE(MAX(ow),0) FROM {$this->db->prefix}lists WHERE user_id=? AND taskview & 4 = 0) )",
                    array($userId, generateUUID(), $name, $time, $time, 1, $userId) );
        $id = $this->db->lastInsertId();
        return (int)$id;
    }


    /**
     * Delete a list by id
     * Return 1 when list record is deleted
     * @param int $listId
     * @return int
     */
    public function deleteListById(int $listId): int
    {
        $this->db->ex("BEGIN");
        $this->db->ex("DELETE FROM {$this->db->prefix}lists WHERE id=?", [$listId]);
        $affected = $this->db->affected();
        if ($affected) {
            $this->db->ex("DELETE FROM {$this->db->prefix}tag2task WHERE list_id=?", [$listId]);
            $this->db->ex("DELETE FROM {$this->db->prefix}todolist WHERE list_id=?", [$listId]);
        }
        $this->db->ex("COMMIT");
        return $affected;
    }


    /**
     * Delete completed task in list
     * Return number of deleted records
     * @param int $listId
     * @return int
     */
    public function deleteCompletedTasksInList(int $listId): int
    {
        $this->db->ex("BEGIN");
        $this->db->ex("DELETE FROM {$this->db->prefix}tag2task WHERE task_id IN
            (SELECT id FROM {$this->db->prefix}todolist WHERE list_id=? and compl=1)",
                array($listId));
        $this->db->ex("DELETE FROM {$this->db->prefix}todolist WHERE list_id=? and compl=1", [$listId]);
        $affected = $this->db->affected();
        $this->db->ex("COMMIT");
        return $affected;
    }
}
