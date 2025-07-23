<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class TaskRepo
{
    protected AbstractDatabase $db;

    const SORT_MANUAL = 0;
    const SORT_MANUAL_REVERSE = 100;
    const SORT_PRIORITY = 1;
    const SORT_PRIORITY_REVERSE = 101;
    const SORT_DUEDATE = 2;
    const SORT_DUEDATE_REVERSE = 101;
    const SORT_DATE_CREATED = 3;
    const SORT_DATE_CREATED_REVERSE = 103;
    const SORT_DATE_EDITED = 4;
    const SORT_DATE_EDITED_REVERSE = 104;
    const SORT_TITLE = 5;
    const SORT_TITLE_REVERSE = 105;

    const SORT_FIELD_ID = 1000;
    const SORT_FIELD_TITLE = 1001;
    const SORT_FIELD_CREATED = 1004;
    const SORT_FIELD_COMPLETED = 1003;
    const SORT_FIELD_EDITED = 1004;
    const SORT_FIELD_PRIORITY = 1005;
    const SORT_FIELD_OW = 1006;

    const FILTER_OPEN = 1;
    const FILTER_COMPLETED = 2;
    const FILTER_EDITED = 4;
    const FILTER_OPEN_AND_EDITED = 5; # 1+4

    function __construct(AbstractDatabase $db)
    {
        $this->db = $db;
    }


    public function findTasks(array $lists, ?bool $compl, array $tags, string $search, int $sort, int $filter = 0, int $limit = 0)
    {
        $makeInts = function (array &$a) { foreach ($a as &$v) $v = (int)$v; };
        $sqlWhere = $sqlWhereListId = $sqlHaving = $sqlLimit = '';

        # list ids (make int)
        if (count($lists) == 0) {
            throw new InvalidArgumentException("No list specified");
        }
        $makeInts($lists);
        $sqlWhereListId = "todo.list_id IN (". implode(",", $lists). ") ";

        # completed flag (null - show both)
        if ($compl === false) {
            $sqlWhere .= ' AND compl=0';
        }
        else if ($compl === true) {
            $sqlWhere .= ' AND compl=1';
        }

        # tags
        if (isset($tags['excludeAll']) && $tags['excludeAll']) {
            # No Tags
            if ($this->db::DBTYPE == DBConnection::DBTYPE_POSTGRES)
                $sqlHaving = "string_agg(tags.name, ',') IS NULL"; // catches if tag name is ''
            else
                $sqlHaving = "tags_ids IS NULL OR tags_ids = ''";
        }
        else {
            if ($tags['includeAny'] ?? false) {
                # Having any tag
                if ($this->db::DBTYPE == DBConnection::DBTYPE_POSTGRES)
                    $sqlHaving = "string_agg(tags.name, ',') != ''";
                else
                    $sqlHaving = "tags_ids != ''";
            }
            if ($tags['include'] ?? 0) {
                # Include tags
                $tagAnd = [];
                foreach ($tags['include'] as $ids) {
                    $makeInts($ids);
                    $tagAnd[] = "task_id IN (SELECT task_id FROM {$this->db->prefix}tag2task WHERE tag_id IN (". implode(',', $ids). "))";
                }
                $sqlWhere .= "\n AND todo.id IN (".
                             "SELECT DISTINCT task_id FROM {$this->db->prefix}tag2task WHERE ". implode(' AND ', $tagAnd). ")";
            }
            if ($tags['exclude'] ?? 0) {
                # Exclude tags
                $makeInts($tags['exclude']);
                $sqlWhere .= "\n AND todo.id NOT IN (SELECT DISTINCT task_id FROM {$this->db->prefix}tag2task ".
                            "WHERE tag_id IN (". implode(',', $tags['exclude']). "))";
            }
        }

        # Search filter
        if ($search != '') {
            if (preg_match("|^#(\d+)$|", $search, $m)) {
                $sqlWhere .= " AND todo.id = ". (int)$m[1];
            }
            else {
                $sqlWhere .= " AND (". $this->db->like("title", "%%%s%%", $search). " OR ". $this->db->like("note", "%%%s%%", $search). ")";
            }
        }

        # Sort
        $sqlSort = "ORDER BY compl ASC, ";
        if ($sort == self::SORT_MANUAL)
                                                        $sqlSort .= "ow ASC";
        elseif ($sort == self::SORT_MANUAL_REVERSE)
                                                        $sqlSort .= "ow DESC";
        elseif ($sort == self::SORT_PRIORITY)
                                                        $sqlSort .= "prio DESC, ddn ASC, duedate ASC, ow ASC";
        elseif ($sort == self::SORT_PRIORITY_REVERSE)
                                                        $sqlSort .= "prio ASC, ddn DESC, duedate DESC, ow DESC";
        elseif ($sort == self::SORT_DUEDATE)
                                                        $sqlSort .= "ddn ASC, duedate ASC, prio DESC, ow ASC";
        elseif ($sort == self::SORT_DUEDATE_REVERSE)
                                                        $sqlSort .= "ddn DESC, duedate DESC, prio ASC, ow DESC";
        elseif ($sort == self::SORT_DATE_CREATED)
                                                        $sqlSort .= "d_created ASC, prio DESC, ow ASC";
        elseif ($sort == self::SORT_DATE_CREATED_REVERSE)
                                                        $sqlSort .= "d_created DESC, prio ASC, ow DESC";
        elseif ($sort == self::SORT_DATE_EDITED)
                                                        $sqlSort .= "d_edited ASC, prio DESC, ow ASC";
        elseif ($sort == self::SORT_DATE_EDITED_REVERSE)
                                                        $sqlSort .= "d_edited DESC, prio ASC, ow DESC";
        elseif ($sort == self::SORT_TITLE)
                                                        $sqlSort .= "title ASC, prio DESC, ow ASC";
        elseif ($sort == self::SORT_TITLE_REVERSE)
                                                        $sqlSort .= "title DESC, prio ASC, ow DESC";
        elseif ($sort == self::SORT_FIELD_ID)           $sqlSort .= "todo.id DESC";
        elseif ($sort == self::SORT_FIELD_TITLE)        $sqlSort .= "title ASC";
        elseif ($sort == self::SORT_FIELD_CREATED)      $sqlSort .= "d_created DESC";
        elseif ($sort == self::SORT_FIELD_COMPLETED)    $sqlSort .= "d_completed DESC";
        elseif ($sort == self::SORT_FIELD_EDITED)       $sqlSort .= "d_edited DESC";
        elseif ($sort == self::SORT_FIELD_PRIORITY)     $sqlSort .= "prio DESC";
        elseif ($sort == self::SORT_FIELD_OW)           $sqlSort .= "ow ASC";
        else
            $sqlSort .= "d_created ASC, prio DESC, ow ASC";             // same as byDateCreated


        $groupConcat = '';
        if ($this->db::DBTYPE == DBConnection::DBTYPE_POSTGRES) {
            $groupConcat =  "array_to_string(array_agg(tags.id), ',') AS tags_ids, string_agg(tags.name, ',') AS tags";
        }
        else {
            $groupConcat = "GROUP_CONCAT(tags.id) AS tags_ids, GROUP_CONCAT(tags.name) AS tags";
        }

        if ($sqlHaving != '')
            $sqlHaving = "HAVING $sqlHaving";

        if     ($filter == self::FILTER_OPEN)             $sqlWhere .= " AND compl=0";
        elseif ($filter == self::FILTER_COMPLETED)        $sqlWhere .= " AND compl=1";
        elseif ($filter == self::FILTER_EDITED)           $sqlWhere .= " AND d_edited > d_created";
        elseif ($filter == self::FILTER_OPEN_AND_EDITED)  $sqlWhere .= " AND compl=0 AND d_edited > d_created";

        if ($limit > 0)
            $sqlLimit = "LIMIT $limit";

        $q = $this->db->dq("
            SELECT todo.*, lists.name list_name, todo.duedate IS NULL AS ddn, $groupConcat
            FROM {$this->db->prefix}todolist AS todo
            INNER JOIN {$this->db->prefix}lists AS lists ON todo.list_id = lists.id
            LEFT JOIN {$this->db->prefix}tag2task AS t2t ON todo.id = t2t.task_id
            LEFT JOIN {$this->db->prefix}tags AS tags ON t2t.tag_id = tags.id
            WHERE $sqlWhereListId $sqlWhere
            GROUP BY todo.id   $sqlHaving
            $sqlSort   $sqlLimit
        ");

        $a = [];
        while ($r = $q->fetchAssoc())
        {
            unset($r['ddn']); # used only for ORDER BY
             $a[] = Task::fromArray($r);
        }

        return $a;
    }

    /**
     * Get Id of a list where task is located by id of this task.
     * Return null if task is not found.
     * Can return 0, but this is unexpected behaviour (task is lost).
     * @param int $id
     * @return null|int
     */
    public function findListIdByTaskId(int $id): ?int
    {
        $r = $this->db->sqa("SELECT list_id FROM {$this->db->prefix}todolist WHERE id = ". (int)$id);
        if (!$r)
            return null;
        return (int)$r['list_id'];
    }

    public function findTaskById(int $id): ?Task
    {
        $groupConcat = '';
        if ($this->db::DBTYPE == DBConnection::DBTYPE_POSTGRES) {
            $groupConcat =  "array_to_string(array_agg(tags.id), ',') AS tags_ids, string_agg(tags.name, ',') AS tags";
        }
        else {
            $groupConcat = "GROUP_CONCAT(tags.id) AS tags_ids, GROUP_CONCAT(tags.name) AS tags";
        }
        $r = $this->db->sqa("
            SELECT todo.*, lists.name list_name, $groupConcat
            FROM {$this->db->prefix}todolist AS todo
            INNER JOIN {$this->db->prefix}lists AS lists ON todo.list_id = lists.id
            LEFT JOIN {$this->db->prefix}tag2task AS t2t ON todo.id = t2t.task_id
            LEFT JOIN {$this->db->prefix}tags AS tags ON t2t.tag_id = tags.id
            WHERE todo.id = $id
            GROUP BY todo.id
        ");
        if ($r)
            return Task::fromArray($r);
        return null;
    }


    /**
     * Get number of tasks created and not completed in specified lists after some timestamp.
     * Does not check access rights.
     * @param array<int,int> $listIds
     * @return array{listId:int,counter:int}[]
     */
    public function counterOfNewTasksInLists(array $listIds): array
    {
        $sqlWhereList = [];
        foreach ($listIds as $listId => $later) {
            $sqlWhereList[] = "(list_id = ". (int)$listId. " AND compl=0 AND d_created > ". (int)$later. ")";
        }
        if (!$sqlWhereList) {
            return [];
        }
        $a = [];
        $sqlWhere = implode(' OR ', $sqlWhereList);
        $q = $this->db->dq("SELECT list_id, COUNT(id) c FROM {$this->db->prefix}todolist WHERE $sqlWhere GROUP BY list_id");
        while ($r = $q->fetchAssoc()) {
            $a[] = [
                'listId' => (int)$r['list_id'],
                'counter' => (int)$r['c'],
            ];
        }
        return $a;
    }

    /**
     * Get ids of tasks created and not completed in specified list after some timestamp.
     * @param int $listId
     * @param int $later
     * @return int[]
     */
    public function idsOfNewTasksInList(int $listId, int $later): array
    {
        $a = [];
        $q = $this->db->dq("SELECT id FROM {$this->db->prefix}todolist WHERE list_id = ? AND compl=0 AND d_created > ?",
            [$listId, $later]);
        while ($r = $q->fetchAssoc()) {
            $a[] = (int)$r['id'];
        }
        return $a;
    }

    /**
     * Delete a task by id
     * Return 1 when task record is deleted
     * @param int $id
     * @return int
     * @throws Exception
     */
    public function deleteTaskById(int $id): int
    {
        $db = DBConnection::instance();
        $db->ex("BEGIN");
        $db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id=?", [$id]);
        //NB: we do not delete unused tags from tags table
        $db->dq("DELETE FROM {$db->prefix}todolist WHERE id=?", [$id]);
        $deleted = $db->affected();
        $db->ex("COMMIT");
        return $deleted;
    }


    /**
     * Update order weights of tasks to change their display order
     * @param array{id:int|string,diff:int}[] $order
     * @return void
     */
    public function changeTaskOrder(array $order)
    {
        /** @var array<int,int[]> */
        $ad = array();
        foreach ($order as $obj) {
            $id = (int) ($obj['id'] ?? 0);
            $diff = (int) ($obj['diff'] ?? 0);
            if ($id === 0 || $diff === 0)
                continue;
            $ad[$diff][] = $id;
        }

        $this->db->ex("BEGIN");
        foreach ($ad as $diff=>$ids) {
            if ($diff >=0)
                $set = "ow=ow+".$diff;
            else
                $set = "ow=ow-".abs($diff);
            $this->db->dq( "UPDATE {$this->db->prefix}todolist SET $set WHERE id IN (". implode(',', $ids). ")" );
        }
        $this->db->ex("COMMIT");
    }


    public function updateTaskProperties(Task $task): int
    {
        $fv = $task->toArray(true);
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

        if (isset($fv['compl']) || isset($fv['list_id'])) {
            # Calculate new order weight
            if ($task->isCompleted)
                $ow = 1 + (int)$this->db->sq("SELECT MAX(ow) FROM {$this->db->prefix}todolist WHERE list_id=? AND compl=1", [$task->listId]);
            else
                $ow = 1 + (int)$this->db->sq("SELECT MAX(ow) FROM {$this->db->prefix}todolist WHERE list_id=? AND compl=0", [$task->listId]);
            $fields[] = "ow=?";
            $values[] = $ow;
            ##$task->ow = $ow;
        }
        $sqlSet = implode(',', $fields);
        $values[] = $task->id;

        $this->db->ex("BEGIN");
        if (isset($fv['list_id'])) {
            $this->db->ex("UPDATE {$this->db->prefix}tag2task SET list_id=? WHERE task_id=?", [$task->listId, $task->id]);
        }
        $this->db->ex("UPDATE {$this->db->prefix}todolist SET $sqlSet WHERE id=?", $values);
        $affected = $this->db->affected();
        $this->db->ex("COMMIT");
        return $affected;
    }


    public function saveTask(Task $task, int $userId)
    {
        if ($task->id === null) {
            # Create new record
            $a = $task->toArray();

            $a['ow'] = 1 + (int)$this->db->sq("SELECT MAX(ow) FROM {$this->db->prefix}todolist WHERE list_id=? AND compl=?",
                    [$task->listId, $task->isCompleted ? 1 : 0]);

            $this->db->ex("BEGIN");
            //TODO:parent_id
            $this->db->dq("INSERT INTO {$this->db->prefix}todolist (uuid,list_id,title,note,d_created,d_edited,prio,duedate,compl,d_completed,ow,extra)
                           VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$task->uuid, $task->listId, $task->title, $task->note, $task->d_created, $task->d_edited, $task->priority, $task->duedate,
                    $a['compl'], $task->d_completed, $a['ow'], $a['extra']] );

            $task->id = (int) $this->db->lastInsertId();

            if ($task->tagNames)
            {
                $tagRepo = new TagRepo($this->db);
                $tags = $tagRepo->getTags($task->tagNames, $userId);
                foreach ($tags as $tag) {
                    $this->db->ex(
                        "INSERT INTO {$this->db->prefix}tag2task (task_id,tag_id,list_id) VALUES (?,?,?)",
                        array($task->id, $tag->id, $task->listId)
                    );
                }
                $task->setTags($tags);
            }
            $this->db->ex("COMMIT");
        }
        else {
            # Update record
            $a = $task->toArray();
            //TODO: if completed flag was changed we have to update the $ow

            $this->db->ex("BEGIN");

            $this->db->ex("DELETE FROM {$this->db->prefix}tag2task WHERE task_id=?", [$task->id]);
            if ($task->tagNames)
            {
                $tagRepo = new TagRepo($this->db);
                $tags = $tagRepo->getTags($task->tagNames, $userId);
                foreach ($tags as $tag) {
                    $this->db->ex(
                        "INSERT INTO {$this->db->prefix}tag2task (task_id,tag_id,list_id) VALUES (?,?,?)",
                        array($task->id, $tag->id, $task->listId)
                    );
                }
                $task->setTags($tags);
            }

            $this->db->dq("UPDATE {$this->db->prefix}todolist SET title=?,note=?,prio=?,duedate=?,d_edited=?,extra=? WHERE id=?",
                [$task->title, $task->note, $task->priority, $task->duedate, $task->d_edited, $a['extra'],  $task->id] );

            $this->db->ex("COMMIT");
        }
    }

}
