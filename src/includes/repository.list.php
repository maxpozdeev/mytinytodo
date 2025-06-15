<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class ListRepo
{
    protected AbstractDatabase $db;

    function __construct(AbstractDatabase $db)
    {
        $this->db = $db;
    }

    /**
     *
     * @param int $userId
     * @param bool $includeAlltasks
     * @return AbstractTasklist[]
     * @throws Exception
     */
    function findListsByUserId(int $userId, bool $includeAlltasks = false): array
    {
        $a = [];

        if ($includeAlltasks)
            $a[] = $this->alltasksListByUserId($userId);

        # taskview & 4 - isHidden flag
        $q = $this->db->dq("SELECT * FROM {$this->db->prefix}lists WHERE user_id=? ORDER BY taskview & 4 ASC, ow ASC, id ASC", [$userId]);
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
     * Get a virtual list with all tasks
     * @param int $userId
     * @return AlltasksList
     * @throws Exception
     */
    public function alltasksListByUserId(int $userId): AlltasksList
    {
        $opts = UserConfig::requestUserDomain($userId, 'alltasks.json');
        $list = AlltasksList::fromArray($opts);
        $list->userId = $userId;
        return $list;
    }


    /**
     * Get all Ids and names of tasklists user owns (as dictonary)
     * @param int $userId
     * @param bool $publicOnly
     * @return array<string,string>
     */
    public function findListNamesByUserId(int $userId, bool $publicOnly = false): array
    {
        $sqlWhere = '';
        if ($publicOnly) {
            $sqlWhere .= " AND published=1";
        }
        $a = array();
        $q = $this->db->dq("SELECT id,name FROM {$this->db->prefix}lists WHERE user_id=? $sqlWhere", [$userId]);
        while ($r = $q->fetchRow()) {
            $a[ (string)$r[0] ] = (string)$r[1];
        }
        return $a;
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
     * Filter ids of lists (max 100) which user owns or are published by other users
     * @param int $userId
     * @param (int|string)[] $listIds
     * @return int[]
     * @throws Exception
     */
    public function filterReadableListsForUser(int $userId, array $listIds): array
    {
        $ids = [];
        foreach ($listIds as $id) {
            $ids[] = (int) $id;
        }
        if (count($ids) > 100) {
            throw new InvalidArgumentException("Limit of lists");
        }
        if (count($ids) == 0)
            return [];
        $strIds = implode(',', $ids);

        $a = [];
        $q = $this->db->dq("SELECT id FROM {$this->db->prefix}lists WHERE id IN ($strIds) AND (user_id=? OR published=1)", [$userId]);
        while ($r = $q->fetchAssoc()) {
            $a[] = (int) $r['id'];
        }
        return $a;
    }


    /**
     * Create new list with name
     * @param string $name
     * @param int $userId
     * @return null|int
     */
    public function createList(string $name, int $userId): ?int
    {
        $name = trim($name);
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
     * Save only changed properties of a list
     * @param TaskList $list
     * @return int
     * @throws InvalidArgumentException
     */
    public function updateListProperties(TaskList $list): int
    {
        $fv = $list->toArray(true);
        if (count($fv) == 0) {
            return 0;
        }

        $fields = [];
        $values = [];
        foreach ($fv as $field => $value) {
            if (!preg_match("/^[a-zA-Z0-9_]+$/", $field))
                throw new InvalidArgumentException("Unexpected table field name: $field");
            $fields[] = "$field=?";
            $values[] = $value;
        }
        $sqlSet = implode(',', $fields);
        $values[] = $list->id;
        $this->db->ex("UPDATE {$this->db->prefix}lists SET $sqlSet WHERE id=?", $values);
        $affected = $this->db->affected();
        return $affected;
    }

    public function updateAlltasksList(AlltasksList $list)
    {
        $opts = $list->toArray(true);
        UserConfig::saveUserDomain($list->userId, 'alltasks.json', $opts);
    }

    // /**
    //  * Change 'published' state of a list
    //  * @param int $listId
    //  * @param bool $published
    //  * @return int
    //  */
    // public function setPublishedOfList(int $listId, bool $published): int
    // {
    //     $this->db->ex("UPDATE {$this->db->prefix}lists SET published=?,d_edited=? WHERE id=?",
    //         [$published ? 1 : 0, time(), $listId] );
    //     $affected = $this->db->affected();
    //     return $affected;
    // }

    // /**
    //  * Change 'showNotes' state of a list
    //  * @param int $listId
    //  * @param bool $show
    //  * @return int
    //  */
    // public function setShowNotesOfList(int $listId, bool $show): int
    // {
    //     $bitwise =  $show ? 'taskview | 2' : 'taskview & ~2';
    //     $this->db->dq("UPDATE {$this->db->prefix}lists SET taskview=$bitwise WHERE id=?", [$listId]);
    //     $affected = $this->db->affected();
    //     return $affected;
    // }

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
