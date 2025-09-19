<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace BackupExtension;

use DBConnection;

class Check
{
    public $lastErrorString = null;
    public $report = '';

    function check(): bool
    {
        $db = DBConnection::instance();
        $msg = [];

        // Task without list
        $count = $db->sq("SELECT COUNT(*) FROM {$db->getPrefix()}todolist WHERE list_id NOT IN (SELECT id FROM {$db->getPrefix()}lists)");
        if ($count) {
            $msg[] = "Tasks without list: $count";
        }

        // Tag without task (not a broblem)
        $count = $db->sq("SELECT COUNT(*) FROM {$db->getPrefix()}tags WHERE id NOT IN (SELECT tag_id FROM {$db->getPrefix()}tag2task)");
        if ($count) {
            $msg[] = "Tags without task: $count";
        }

        // tag2task no list
        $count = $db->sq("SELECT COUNT(*) FROM {$db->getPrefix()}tag2task WHERE list_id NOT IN (SELECT id FROM {$db->getPrefix()}lists)");
        if ($count) {
            $msg[] = "tag2task no list: $count";
        }

        // tag2task no tag
        $count = $db->sq("SELECT COUNT(*) FROM {$db->getPrefix()}tag2task WHERE tag_id NOT IN (SELECT id FROM {$db->getPrefix()}tags)");
        if ($count) {
            $msg[] = "tag2task no tag: $count";
        }

        // tag2task no task
        $count = $db->sq("SELECT COUNT(*) FROM {$db->getPrefix()}tag2task WHERE task_id NOT IN (SELECT id FROM {$db->getPrefix()}todolist)");
        if ($count) {
            $msg[] = "tag2task no task: $count";
        }

        $count = 0;
        $uniqTag = []; // lowerTag => [id, tag]
        $nonuniqTag = []; // id => [tag, lowerTag, uniqId, uniqTag, taskCount]
        $q = $db->dq("SELECT id,name,COUNT(task_id) c FROM {$db->getPrefix()}tags t LEFT JOIN {$db->getPrefix()}tag2task tt ON t.id=tt.tag_id GROUP BY id ORDER BY id");
        while ($r = $q->fetchAssoc()) {
            $v = mb_strtolower((string)$r['name'], 'UTF-8');
            if (!isset($uniqTag[$v])) {
                $uniqTag[$v] = [$r['id'], $r['name']];
            }
            else {
                $count++;
                $nonuniqTag[$r['id']] = [$r['name'], $v, $uniqTag[$v][0], $uniqTag[$v][1], $r['c']];
            }
        }
        if ($count > 0) {
            $msg[] = "Non-unique tags: $count";
            foreach ($nonuniqTag as $id => $a) {
                $msg[] = " ID:{$id} Tag:{$a[0]} (tasks: {$a[4]}) same as ID:{$a[2]} Tag:{$a[3]}";
            }
        }

        if (count($msg) == 0) {
            $msg[] = "OK";
        }

        $this->report = implode("\n", $msg);
        return true;
    }

    function repair(): bool
    {
        $db = DBConnection::instance();

        $db->ex("BEGIN");

        // Task without list
        $count = (int)$db->sq("SELECT COUNT(*) FROM {$db->getPrefix()}todolist WHERE list_id NOT IN (SELECT id FROM {$db->getPrefix()}lists)");
        if ($count > 0) {
            // Move to new list
            $listID = \DBCore::default()->createListWithName("Restored tasks");
            $db->ex("UPDATE {$db->getPrefix()}todolist SET list_id=? WHERE list_id NOT IN (SELECT id FROM {$db->getPrefix()}lists)", [$listID]);
        }

        //Tags
        $db->ex("DELETE FROM {$db->getPrefix()}tags WHERE id NOT IN (SELECT tag_id FROM {$db->getPrefix()}tag2task)");
        $db->ex("DELETE FROM {$db->getPrefix()}tag2task WHERE task_id NOT IN (SELECT id FROM {$db->getPrefix()}todolist)");
        $db->ex("DELETE FROM {$db->getPrefix()}tag2task WHERE tag_id NOT IN (SELECT id FROM {$db->getPrefix()}tags)");

        //Non-unique tags replace with first unique
        $uniqTag = [];
        $replace = [];
        $q = $db->dq("SELECT id,name FROM {$db->getPrefix()}tags t LEFT JOIN {$db->getPrefix()}tag2task tt ON t.id=tt.tag_id GROUP BY id ORDER BY id");
        while ($r = $q->fetchAssoc()) {
            $v = mb_strtolower((string)$r['name'], 'UTF-8');
            if (!isset($uniqTag[$v])) {
                $uniqTag[$v] = $r['id'];
            }
            else {
                $replace[$r['id']] = $uniqTag[$v];
            }
        }
        foreach ($replace as $id => $newId) {
            $db->ex("UPDATE {$db->getPrefix()}tag2task SET tag_id=? WHERE tag_id=?", [$newId, $id]);
        }
        $db->ex("DELETE FROM {$db->getPrefix()}tags WHERE id NOT IN (SELECT tag_id FROM {$db->getPrefix()}tag2task)");


        // TODO: tag2task no list ?

        $db->ex("COMMIT");
        return true;
    }

}
