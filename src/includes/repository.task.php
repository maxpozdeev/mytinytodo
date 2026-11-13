<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025-2026 Max Pozdeev <maxpozdeev@gmail.com>
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
    const SORT_DUEDATE_REVERSE = 102;
    const SORT_DATE_CREATED = 3;
    const SORT_DATE_CREATED_REVERSE = 103;
    const SORT_DATE_EDITED = 4;
    const SORT_DATE_EDITED_REVERSE = 104;
    const SORT_TITLE = 5;
    const SORT_TITLE_REVERSE = 105;

    const SORT_FIELD_ID = 1000;
    const SORT_FIELD_TITLE = 1001;
    const SORT_FIELD_CREATED = 1002;
    const SORT_FIELD_COMPLETED = 1003;
    const SORT_FIELD_EDITED = 1004;
    const SORT_FIELD_PRIORITY = 1005;
    const SORT_FIELD_OW = 1006;

    const FILTER_OPEN = 1;
    const FILTER_COMPLETED = 2;
    const FILTER_EDITED = 4;
    const FILTER_OPEN_AND_EDITED = 5; # 1+4

    const SEARCH_TITLE = 1;
    const SEARCH_NOTE = 2;
    const SEARCH_TAGS = 4;

    function __construct(AbstractDatabase $db)
    {
        $this->db = $db;
    }


    public function findTasks(array $lists, ?bool $compl, array $tags, string $search, int $sort, string $due = '', int $searchOptions = 0, int $filter = 0, int $limit = 0, ?TaskPaginator $paginator = null)
    {
        $makeInts = function (array &$a) { foreach ($a as &$v) $v = (int)$v; };
        $sqlWhere = $sqlWhereListId = $sqlLimit = '';

        # list ids (make int)
        if (count($lists) == 0) {
            throw new InvalidArgumentException("No list specified");
        }
        $makeInts($lists);
        $sqlWhereListId = "todo.list_id IN (". implode(",", $lists). ") ";

        # Duedate filter
        if ($due !== '') {
            $ts = time();
            $today = date('Y-m-d', $ts);
            if ($due === 'past') {
                $sqlWhere .= " AND todo.duedate IS NOT NULL AND todo.duedate < '$today'";
            }
            else if ($due === 'today') {
                $date2 = date('Y-m-d', $ts + 86400); # tomorrow
                $sqlWhere .= " AND (todo.duedate = '$today' OR todo.duedate = '$date2')";
            }
            else if ($due === 'soon') {
                $date2 = date('Y-m-d', $ts + 7*86400); # 7 days
                $sqlWhere .= " AND todo.duedate IS NOT NULL AND todo.duedate BETWEEN '$today' AND '$date2'";
            }
            else $due = '';
        }

        # completed flag (null - show both)
        if ($compl === false) {
            $sqlWhere .= ' AND compl=0';
        }
        else if ($compl === true) {
            $sqlWhere .= ' AND compl=1';
        }

        $searchTitle = (($searchOptions & static::SEARCH_TITLE) === static::SEARCH_TITLE);
        $searchNote = (($searchOptions & static::SEARCH_NOTE) === static::SEARCH_NOTE);
        $searchTags = (($searchOptions & static::SEARCH_TAGS) === static::SEARCH_TAGS);
        if (!$searchTitle && !$searchNote && !$searchTags) {
            $searchTitle = true;
            $searchNote = true;
        }

        # tags
        if (isset($tags['excludeAll']) && $tags['excludeAll']) {
            # No Tags
            $sqlWhere = " AND NOT EXISTS (SELECT 1 FROM {$this->db->prefix}tag2task t2t WHERE todo.id = t2t.task_id)";
        }
        else {
            if ($tags['includeAny'] ?? false) {
                # Having any tag
                $sqlWhere = " AND EXISTS (SELECT 1 FROM {$this->db->prefix}tag2task t2t WHERE todo.id = t2t.task_id)";
            }
            if ($tags['include'] ?? 0) {
                # Include tags
                $tagAnd = [];
                foreach ($tags['include'] as $ids) {
                    $makeInts($ids);
                    $tagAnd[] = "\t\t\t\t task_id IN (SELECT task_id FROM {$this->db->prefix}tag2task WHERE tag_id IN (". implode(',', $ids). "))";
                }
                $sqlWhere .= "
                    AND todo.id IN (
                        SELECT DISTINCT task_id FROM {$this->db->prefix}tag2task WHERE \n". implode(" AND \n", $tagAnd). "
                    )";
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
                $sa = [];
                if ($searchTitle) $sa[] = $this->db->like("title", "%%%s%%", $search);
                if ($searchNote) $sa[] = $this->db->like("note", "%%%s%%", $search);
                if ($searchTags) {
                    if ($tags['search']) $sa[] = "todo.id IN (SELECT task_id FROM {$this->db->prefix}tag2task WHERE tag_id IN (". implode(",", $tags['search']). "))";
                    else $sa[] = "todo.id IN (0)";
                }
                if ($sa) {
                    $sqlWhere .= " AND (". implode(" OR ", $sa). ")";
                }
            }
        }

        # Sort
        $collate = ($this->db::DBTYPE === DBConnection::DBTYPE_SQLITE) ? "COLLATE {$this->db->orderCollation}" : "";
        $sqlSort = "ORDER BY compl ASC, ";
        if ($sort == self::SORT_MANUAL)                     $sqlSort .= "ow ASC, id ASC";
        elseif ($sort == self::SORT_MANUAL_REVERSE)         $sqlSort .= "ow DESC, id DESC";
        elseif ($sort == self::SORT_PRIORITY)               $sqlSort .= "prio DESC, duedate IS NULL ASC, duedate ASC, id ASC";
        elseif ($sort == self::SORT_PRIORITY_REVERSE)       $sqlSort .= "prio ASC, duedate IS NULL DESC, duedate DESC, id DESC";
        elseif ($sort == self::SORT_DUEDATE)                $sqlSort .= "duedate IS NULL ASC, duedate ASC, prio DESC, id ASC";
        elseif ($sort == self::SORT_DUEDATE_REVERSE)        $sqlSort .= "duedate IS NULL DESC, duedate DESC, prio ASC, id DESC";
        elseif ($sort == self::SORT_DATE_CREATED)           $sqlSort .= "d_created ASC, prio DESC, id ASC";
        elseif ($sort == self::SORT_DATE_CREATED_REVERSE)   $sqlSort .= "d_created DESC, prio ASC, id DESC";
        elseif ($sort == self::SORT_DATE_EDITED)            $sqlSort .= "d_edited ASC, prio DESC, id ASC";
        elseif ($sort == self::SORT_DATE_EDITED_REVERSE)    $sqlSort .= "d_edited DESC, prio ASC, id DESC";
        elseif ($sort == self::SORT_TITLE)                  $sqlSort .= "title $collate ASC, prio DESC, id ASC";
        elseif ($sort == self::SORT_TITLE_REVERSE)          $sqlSort .= "title $collate DESC, prio ASC, id DESC";
        elseif ($sort == self::SORT_FIELD_ID)               $sqlSort .= "todo.id DESC";
        elseif ($sort == self::SORT_FIELD_TITLE)            $sqlSort .= "title $collate ASC";
        elseif ($sort == self::SORT_FIELD_CREATED)          $sqlSort .= "d_created DESC";
        elseif ($sort == self::SORT_FIELD_COMPLETED)        $sqlSort .= "d_completed DESC";
        elseif ($sort == self::SORT_FIELD_EDITED)           $sqlSort .= "d_edited DESC";
        elseif ($sort == self::SORT_FIELD_PRIORITY)         $sqlSort .= "prio DESC";
        elseif ($sort == self::SORT_FIELD_OW)               $sqlSort .= "ow ASC";
        else                                                $sqlSort .= "d_created DESC, prio ASC, id DESC"; // same as by Date Created Desc


        $groupConcat = '';
        if ($this->db::DBTYPE == DBConnection::DBTYPE_POSTGRES) {
            $groupConcat =  "array_to_string(array_agg(tags.id), ',') AS tags_ids, string_agg(tags.name, ',') AS tags";
        }
        else {
            $groupConcat = "GROUP_CONCAT(tags.id) AS tags_ids, GROUP_CONCAT(tags.name) AS tags";
        }

        if     ($filter == self::FILTER_OPEN)             $sqlWhere .= " AND compl=0";
        elseif ($filter == self::FILTER_COMPLETED)        $sqlWhere .= " AND compl=1";
        elseif ($filter == self::FILTER_EDITED)           $sqlWhere .= " AND d_edited > d_created";
        elseif ($filter == self::FILTER_OPEN_AND_EDITED)  $sqlWhere .= " AND compl=0 AND d_edited > d_created";

        if ($paginator) {
            $limit = 0;
            $paginatorTotal = (int)$this->db->sq("SELECT COUNT(*) FROM {$this->db->prefix}todolist AS todo WHERE $sqlWhereListId $sqlWhere");
            $paginator->updateTotal($paginatorTotal);
            $sqlLimit = $paginator->sql();
        }
        else if ($limit > 0) {
            $sqlLimit = "LIMIT $limit";
        }

        $q = $this->db->dq("
            SELECT todo.*, lists.name AS list_name, tags_agg.tags_ids as tags_ids, tags_agg.tags as tags
            FROM {$this->db->prefix}todolist AS todo
            INNER JOIN {$this->db->prefix}lists AS lists ON todo.list_id = lists.id
            LEFT JOIN (
                SELECT t2t.task_id, $groupConcat
                FROM {$this->db->prefix}tag2task AS t2t
                INNER JOIN {$this->db->prefix}tags AS tags ON t2t.tag_id = tags.id
                GROUP BY t2t.task_id
            ) AS tags_agg ON todo.id = tags_agg.task_id
            WHERE $sqlWhereListId $sqlWhere
            $sqlSort   $sqlLimit
        ");

        $a = [];
        while ($r = $q->fetchAssoc()) {
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
            SELECT todo.*, lists.name AS list_name, tags_agg.tags_ids as tags_ids, tags_agg.tags as tags
            FROM {$this->db->prefix}todolist AS todo
            INNER JOIN {$this->db->prefix}lists AS lists ON todo.list_id = lists.id
            LEFT JOIN (
                SELECT t2t.task_id, $groupConcat
                FROM {$this->db->prefix}tag2task AS t2t
                INNER JOIN {$this->db->prefix}tags AS tags ON t2t.tag_id = tags.id
                GROUP BY t2t.task_id
            ) AS tags_agg ON todo.id = tags_agg.task_id
            WHERE todo.id = $id
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
     * Place a task immediately after or before the anchor task.
     * Task and anchor must be located in the same list and have the same completion flag.
     * Returns null when the anchor task is not found
     * and false when the anchor is in another list or has another completion flag.
     * @param Task $task
     * @param int $anchorId
     * @param bool $isAfter Place after the anchor (true) or before it (false)
     * @return bool|null
     * @throws Exception
     */
    public function changeTaskPosition(Task $task, int $anchorId, bool $isAfter): ?bool
    {
        if ($task->id === null) {
            throw new InvalidArgumentException("Task id is not set");
        }
        $anchor = $this->db->sqa(
            "SELECT id, list_id, compl, ow FROM {$this->db->prefix}todolist WHERE id=?",
            [$anchorId]
        );
        if (!$anchor) {
            return null;
        }
        $listId = (int)$task->listId;
        $compl = $task->isCompleted ? 1 : 0;
        if ((int)$anchor['list_id'] !== $listId || (int)$anchor['compl'] !== $compl) {
            return false;
        }

        $anchorOw = (int)$anchor['ow'];
        $newOw = null;

        $this->db->ex("BEGIN");
        try {
            if ($isAfter) {
                # is there a gap right after the anchor?
                $nextOw = $this->db->sq(
                    "SELECT MIN(ow) FROM {$this->db->prefix}todolist WHERE list_id=? AND compl=? AND ow>? AND id<>?",
                    [$listId, $compl, $anchorOw, $task->id]
                );
                $newOw = $anchorOw + 1;
                if ($nextOw !== null && (int)$nextOw <= $anchorOw + 1) {
                    # no gap - shift the following tasks
                    $this->db->ex(
                        "UPDATE {$this->db->prefix}todolist SET ow=ow+1 WHERE list_id=? AND compl=? AND ow>? AND id<>?",
                        [$listId, $compl, $anchorOw, $task->id]
                    );
                }
            }
            else {
                # is there a gap right before the anchor?
                $prevOw = $this->db->sq(
                    "SELECT MAX(ow) FROM {$this->db->prefix}todolist WHERE list_id=? AND compl=? AND ow<? AND id<>?",
                    [$listId, $compl, $anchorOw, $task->id]
                );
                $hasGap = ($prevOw === null) ? ($anchorOw > 1) : ((int)$prevOw + 1 < $anchorOw);
                if ($hasGap) {
                    $newOw = $anchorOw - 1;
                }
                else {
                    # no gap - shift the anchor and the following tasks
                    $this->db->ex(
                        "UPDATE {$this->db->prefix}todolist SET ow=ow+1 WHERE list_id=? AND compl=? AND ow>=? AND id<>?",
                        [$listId, $compl, $anchorOw, $task->id]
                    );
                    $newOw = $anchorOw;
                }
            }
            $this->db->ex("UPDATE {$this->db->prefix}todolist SET ow=? WHERE id=?", [$newOw, $task->id]);
            $this->db->ex("COMMIT");
        }
        catch (Exception $e) {
            $this->db->ex("ROLLBACK");
            throw $e;
        }

        $task->ow = $newOw;
        return true;
    }


    /**
     * Rewrite order weights of tasks in the list as 10, 20, 30, ...
     * Can be used to compact weights after many insertions.
     * @param int $listId
     * @param int $compl
     * @return int number of updated tasks
     * @throws Exception
     */
    public function renumberTaskOrder(int $listId, int $compl): int
    {
        $q = $this->db->dq(
            "SELECT id FROM {$this->db->prefix}todolist WHERE list_id=? AND compl=? ORDER BY ow ASC, id ASC",
            [$listId, $compl]
        );
        $ids = [];
        while ($r = $q->fetchAssoc()) {
            $ids[] = (int)$r['id'];
        }
        if (!$ids) {
            return 0;
        }
        $step = 10;
        $this->db->ex("BEGIN");
        try {
            $ow = $step;
            foreach ($ids as $id) {
                $this->db->ex("UPDATE {$this->db->prefix}todolist SET ow=? WHERE id=?", [$ow, $id]);
                $ow += $step;
            }
            $this->db->ex("COMMIT");
        }
        catch (Exception $e) {
            $this->db->ex("ROLLBACK");
            throw $e;
        }
        return count($ids);
    }


    /**
     * Add and/or remove tags for multiple tasks in a single transaction.
     * @param Task[] $tasks
     * @param int[] $addTagIds
     * @param int[] $removeTagIds
     * @return int[] ids of changed tasks
     * @throws Exception
     */
    public function updateTasksTags(array $tasks, array $addTagIds, array $removeTagIds): array
    {
        $addTagIds = array_values(array_unique(array_map('intval', $addTagIds)));
        $removeTagIds = array_values(array_unique(array_map('intval', $removeTagIds)));
        if (!$addTagIds && !$removeTagIds) {
            return [];
        }

        $changed = [];
        $this->db->ex("BEGIN");
        try {
            foreach ($tasks as $task) {
                foreach ($addTagIds as $tagId) {
                    $exists = (int)$this->db->sq(
                        "SELECT COUNT(*) FROM {$this->db->prefix}tag2task WHERE task_id=? AND tag_id=?",
                        [$task->id, $tagId]
                    );
                    if ($exists) {
                        continue;
                    }
                    $this->db->ex(
                        "INSERT INTO {$this->db->prefix}tag2task (task_id,tag_id,list_id) VALUES (?,?,?)",
                        [$task->id, $tagId, $task->listId]
                    );
                    $changed[$task->id] = true;
                }
                foreach ($removeTagIds as $tagId) {
                    $this->db->ex(
                        "DELETE FROM {$this->db->prefix}tag2task WHERE task_id=? AND tag_id=?",
                        [$task->id, $tagId]
                    );
                    if ($this->db->affected()) {
                        $changed[$task->id] = true;
                    }
                }
            }
            $this->db->ex("COMMIT");
        }
        catch (Exception $e) {
            $this->db->ex("ROLLBACK");
            throw $e;
        }

        return array_map('intval', array_keys($changed));
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
                $ow = 10 + (int)$this->db->sq("SELECT MAX(ow) FROM {$this->db->prefix}todolist WHERE list_id=? AND compl=1", [$task->listId]);
            else
                $ow = 10 + (int)$this->db->sq("SELECT MAX(ow) FROM {$this->db->prefix}todolist WHERE list_id=? AND compl=0", [$task->listId]);
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


    public function saveTask(Task $task, TaskList $list)
    {
        if ($list->id !== $task->listId) {
            throw new InvalidArgumentException("Unexpected list argument");
        }

        if ($task->id === null) {
            # Create new record
            $a = $task->toArray();

            $a['ow'] = 10 + (int)$this->db->sq("SELECT MAX(ow) FROM {$this->db->prefix}todolist WHERE list_id=? AND compl=?",
                    [$task->listId, $task->isCompleted ? 1 : 0]);

            $this->db->ex("BEGIN");
            //TODO:parent_id
            $this->db->dq("INSERT INTO {$this->db->prefix}todolist (uuid,list_id,title,note,d_created,d_edited,prio,duedate,compl,d_completed,ow,extra)
                           VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$task->uuid, $task->listId, $task->title, $task->note, $task->d_created, $task->d_edited, $task->priority, $task->duedate,
                    $a['compl'], $task->d_completed, $a['ow'], $a['extra']] );

            $task->id = (int) $this->db->lastInsertId();
            $task->ow = $a['ow'];

            if ($task->tagNames)
            {
                $tagRepo = new TagRepo($this->db);
                $tags = $tagRepo->getTags($task->tagNames, $list->userId);
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
                $tags = $tagRepo->getTags($task->tagNames, $list->userId);
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



    /**
     * Get Ids of lists where tasks are located.
     * Return empty array if tasks are not found.
     * @param int[] $ids
     * @return array<string, int> # id => list_id
     */
    public function findListIdsByTaskIds(array $ids): array
    {
        # convert to ints
        foreach ($ids as &$id) {
            $id = (int)$id;
        }
        $strIds = implode(',', $ids);
        $dict = [];
        $q = $this->db->dq("SELECT id, list_id FROM {$this->db->prefix}todolist WHERE id IN ($strIds)");
        while ($r = $q->fetchAssoc()) {
            $dict[(string)$r['id']] = (int)$r['list_id'];
        }
        return $dict;
    }

    /**
     * Delete multiple tasks by their ids
     * Return number of task records deleted
     * @param int[] $ids
     * @return int
     * @throws Exception
     */
    public function deleteTasksByIds(array $ids): int
    {
        # convert to ints
        foreach ($ids as &$id) {
            $id = (int)$id;
        }
        $strIds = implode(',', $ids);
        $db = DBConnection::instance();
        $db->ex("BEGIN");
        $db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id IN ($strIds)");
        //NB: we do not delete unused tags from tags table
        $db->dq("DELETE FROM {$db->prefix}todolist WHERE id IN ($strIds)");
        $deleted = $db->affected();
        $db->ex("COMMIT");
        return $deleted;
    }

}


class TaskPaginator
{
    public int $limit = 0;
    public int $offset = 0; // starts from 0
    public int $page = 1;   // starts from 1

    public int $total = 0;

    public function __construct(int $limit , int $offset)
    {
        if ($limit < 0) $limit = 0;
        if ($offset < 0) $offset = 0;
        if (!$limit) throw new InvalidArgumentException("limit can not be zero");
        $this->limit = $limit;
        $this->offset = $offset;
        $this->page = 1 + (int)floor($offset / $limit);
    }

    public static function paginatorWithPageAndLimit(int $page, int $limit): TaskPaginator
    {
        if ($page < 1) $page = 1;
        if ($limit < 0) $limit = 0;
        if (!$limit) throw new InvalidArgumentException("limit can not be zero");
        return new static($limit, ($page-1)*$limit);
    }

    function sql(): string
    {
        $sql = "LIMIT ". $this->limit;
        if ($this->offset) {
            $sql .= " OFFSET ". $this->offset;
        }
        return $sql;
    }

    function updateTotal(int $total)
    {
        $this->total = $total < 0 ? 0 : $total;
        $totalPages = (int)ceil($this->total / $this->limit);
        if ($totalPages < 1) $totalPages = 1;
        if ($this->page > $totalPages) {
            $this->page = $totalPages;
            $this->offset = ($this->page - 1) * $this->limit;
        }
    }
}
