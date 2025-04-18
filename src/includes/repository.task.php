<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class TaskRepo
{
    protected AbstractDatabase $db;

    function __construct(AbstractDatabase $db)
    {
        $this->db = $db;
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
            SELECT todo.*, $groupConcat
            FROM {$this->db->prefix}todolist AS todo
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
}
