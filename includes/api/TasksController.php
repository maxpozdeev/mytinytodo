<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

require_once(MTTINC. 'markup.php');
require_once(MTTINC. 'smartsyntax.php');

class TasksController extends ApiController {

    /**
     * Get tasks.
     * Filters are set with query parameters.
     * @return void
     * @throws Exception
     */
    function get()
    {
        $listId = (int)_get('list');
        checkReadAccess($listId);
        $db = DBConnection::instance();
        $dbcore = DBCore::default();

        $sqlWhere = $sqlWhereListId = '';
        $userLists = [];
        if ($listId == -1) {
            $userLists = $this->getUserListsSimple();
            $userListsIds = implode(',', array_keys($userLists));
            $sqlWhereListId = "todo.list_id IN ($userListsIds) ";
        }
        else {
            $sqlWhereListId = "todo.list_id=". $listId;
        }
        if (_get('compl') == 0) {
            $sqlWhere .= ' AND compl=0';
        }

        $tag = trim(_get('t'));
        if ($tag != '')
        {
            $at = explode(',', $tag);
            $tagIds = array(); # [ [id1,id2], [id3]... ]
            $tagExIds = array();
            foreach ($at as $atv) {
                $atv = trim($atv);
                if ($atv == '' || $atv == '^') continue;
                if (substr($atv,0,1) == '^') {
                    array_push($tagExIds, ...$dbcore->getTagIdsByName(substr($atv,1)));
                } else {
                    $tagIds[] = $dbcore->getTagIdsByName($atv);
                }
            }

            // Include tags
            if (count($tagIds) > 0) {
                $tagAnd = [];
                foreach ($tagIds as $ids) {
                    $tagAnd[] = "task_id IN (SELECT task_id FROM {$db->prefix}tag2task WHERE tag_id IN (". implode(',', $ids). "))";
                }
                $sqlWhere .= "\n AND todo.id IN (".
                             "SELECT DISTINCT task_id FROM {$db->prefix}tag2task WHERE ". implode(' AND ', $tagAnd). ")";
            }

            // Exclude tags
            if (count($tagExIds) > 0) {
                $sqlWhere .= "\n AND todo.id NOT IN (SELECT DISTINCT task_id FROM {$db->prefix}tag2task ".
                            "WHERE tag_id IN (". implode(',', $tagExIds). "))";
            }
        }

        $s = trim(_get('s'));
        if ($s != '') {
            if (preg_match("|^#(\d+)$|", $s, $m)) {
                $sqlWhere .= " AND todo.id = ". (int)$m[1];
            }
            else {
                $sqlWhere .= " AND (". $db->like("title", "%%%s%%", $s). " OR ". $db->like("note", "%%%s%%", $s). ")";
            }
        }

        $sort = (int)_get('sort');
        $sqlSort = "ORDER BY compl ASC, ";
        // sortings are same as in DBCore::getTasksByListId
        if ($sort == 0) $sqlSort .= "ow ASC";                                           // byHand
        elseif ($sort == 100) $sqlSort .= "ow DESC";                                    // byHand (reverse)
        elseif ($sort == 1) $sqlSort .= "prio DESC, ddn ASC, duedate ASC, ow ASC";      // byPrio
        elseif ($sort == 101) $sqlSort .= "prio ASC, ddn DESC, duedate DESC, ow DESC";  // byPrio (reverse)
        elseif ($sort == 2) $sqlSort .= "ddn ASC, duedate ASC, prio DESC, ow ASC";      // byDueDate
        elseif ($sort == 102) $sqlSort .= "ddn DESC, duedate DESC, prio ASC, ow DESC";  // byDueDate (reverse)
        elseif ($sort == 3) $sqlSort .= "d_created ASC, prio DESC, ow ASC";             // byDateCreated
        elseif ($sort == 103) $sqlSort .= "d_created DESC, prio ASC, ow DESC";          // byDateCreated (reverse)
        elseif ($sort == 4) $sqlSort .= "d_edited ASC, prio DESC, ow ASC";              // byDateModified
        elseif ($sort == 104) $sqlSort .= "d_edited DESC, prio ASC, ow DESC";           // byDateModified (reverse)
        elseif ($sort == 5) $sqlSort .= "title ASC, prio DESC, ow ASC";                 // byTitle
        elseif ($sort == 105) $sqlSort .= "title DESC, prio ASC, ow DESC";              // byTitle (reverse)
        else $sqlSort .= "ow ASC";

        $t = array();
        $t['total'] = 0;
        $t['list'] = array();
        $t['time'] = time();

        $groupConcat = '';
        if ($db::DBTYPE == DBConnection::DBTYPE_POSTGRES) {
            $groupConcat =  "array_to_string(array_agg(tags.id), ',') AS tags_ids, string_agg(tags.name, ',') AS tags";
        }
        else {
            $groupConcat = "GROUP_CONCAT(tags.id) AS tags_ids, GROUP_CONCAT(tags.name) AS tags";
        }

        $q = $db->dq("
            SELECT todo.*, todo.duedate IS NULL AS ddn, $groupConcat
            FROM {$db->prefix}todolist AS todo
            LEFT JOIN {$db->prefix}tag2task AS t2t ON todo.id = t2t.task_id
            LEFT JOIN {$db->prefix}tags AS tags ON t2t.tag_id = tags.id
            WHERE $sqlWhereListId $sqlWhere
            GROUP BY todo.id
            $sqlSort
        ");

        while ($r = $q->fetchAssoc())
        {
            $t['total']++;
            if ($listId == -1 && $r['list_id']) {
                $r['list_name'] = $userLists[ (string)$r['list_id'] ] ?? '((undefined))';
            }
            $t['list'][] = $this->prepareTaskRow($r);
        }
        if (_get('setCompl') && haveWriteAccess($listId)) {
            ListsController::setListShowCompletedById($listId, !(_get('compl') == 0) );
        }
        if (_get('saveSort') == 1 && haveWriteAccess($listId)) {
            ListsController::setListSortingById($listId, $sort);
        }
        $this->response->data = $t;
    }

    /**
     * Create new task
     * action: newSimple or newFull
     * @return void
     * @throws Exception
     */
    function post()
    {
        $action = $this->req->jsonBody['action'] ?? '';
        if ($action == 'order') { //compatibility
            checkWriteAccess();
            $this->response->data = $this->changeTaskOrder();
        }
        else {
            $listId = (int)($this->req->jsonBody['list'] ?? 0);
            checkWriteAccess($listId);
            if ($action == 'newFull') {
                $this->response->data = $this->fullNewTaskInList($listId);
            }
            else {
                $this->response->data = $this->newTaskInList($listId);
            }
        }
    }

    /**
     * Actions with multiple tasks
     * @return void
     * @throws Exception
     */
    function put()
    {
        checkWriteAccess();
        $action = $this->req->jsonBody['action'] ?? '';
        switch ($action) {
            case 'order': $this->response->data = $this->changeTaskOrder(); break;
            default:      $this->response->data = ['total' => 0]; // error 400 ?
        }
    }


    /**
     * Delete task by Id
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    function deleteId($id)
    {
        checkWriteAccess();
        $this->response->data = $this->deleteTask((int)$id);
    }

    /**
     * Edit some properties of Task
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    function putId($id)
    {
        checkWriteAccess();
        $id = (int)$id;

        if (!DBCore::default()->taskExists($id)) {
            $this->response->data = ['total' => 0];
            return;
        }

        $action = $this->req->jsonBody['action'] ?? '';
        switch ($action) {
            case 'edit':     $this->response->data = $this->editTask($id);     break;
            case 'complete': $this->response->data = $this->completeTask($id); break;
            case 'note':     $this->response->data = $this->editNote($id);     break;
            case 'move':     $this->response->data = $this->moveTask($id);     break;
            case 'priority': $this->response->data = $this->priorityTask($id); break;
            case 'delete':   $this->response->data = $this->deleteTask($id);   break; //compatibility
            default:         $this->response->data = ['total' => 0];
        }
    }


    /**
     * Parse task input string to components for representing in edit/add form
     * @return void
     * @throws Exception
     */
    function postTitleParse()
    {
        checkWriteAccess();
        $t = array(
            'title' => trim( $this->req->jsonBody['title'] ?? '' ),
            'prio' => 0,
            'tags' => '',
            'duedate' => '',
        );
        if (Config::get('smartsyntax') != 0 && (false !== $a = parseSmartSyntax($t['title'])))
        {
            $t['title'] = (string) ($a['title'] ?? '');
            $t['prio'] = (int) ($a['prio'] ?? 0);
            $t['tags'] = (string) ($a['tags'] ?? '');
            if (isset($a['duedate']) && $a['duedate'] != '') {
                $dueA = $this->prepareDuedate($a['duedate']);
                $t['duedate'] = $dueA['formatted'];
            }
        }
        $this->response->data = $t;
    }


    function postNewCounter()
    {
        checkReadAccess();
        $lists = $this->req->jsonBody['lists'] ?? [];
        if (!is_array($lists)) $lists = [];
        $userLists = []; // [string]
        if (!haveWriteAccess()) {
            $userLists = $this->getUserListsSimple(true);
            if ($userLists) {
                $sqlWhereList = "AND list_id IN (". implode(',', $userLists). ")";
                // remove lists without access granted
                $lists = array_filter($lists, function($item) use ($userLists) {
                    return in_array( (string)($item['listId'] ?? ''), $userLists );
                });
            }
        }
        $sqlWhereList = [];
        foreach ($lists as $item) {
            $later = (int) ($item['later'] ?? 0);
            $sqlWhereList[] = "(list_id = ". (int)$item['listId']. " AND compl=0 AND d_created > $later)";
        }

        $db = DBConnection::instance();
        $a = [];

        if ($sqlWhereList) {
            $sqlWhere = implode(' OR ', $sqlWhereList);
            $q = $db->dq("SELECT list_id, COUNT(id) c FROM {$db->prefix}todolist
                          WHERE $sqlWhere GROUP BY list_id");
            while ($r = $q->fetchAssoc()) {
                $a[] = [
                    'listId' => (int)$r['list_id'],
                    'counter' => (int)$r['c'],
                ];
            }
        }

        $b = [];
        $list = (int) ($this->req->jsonBody['list'] ?? 0);
        $later = (int) ($this->req->jsonBody['later'] ?? 0);
        if ($list > 0 && $later > 0 && (!$userLists || in_array((string)$list, $userLists))) {
            $q = $db->dq("SELECT id FROM {$db->prefix}todolist
                          WHERE list_id = $list AND compl=0 AND d_created > $later");
            while ($r = $q->fetchAssoc()) {
                $b[] = (int)$r['id'];
            }
        }

        $this->response->data = [
            'ok' => true,
            'total' => count($b) + count($a),
            'tasks' => $b,
            'lists' => $a
        ];
    }

    /* Private Functions */

    private function newTaskInList(int $listId): ?array
    {
        $db = DBConnection::instance();
        $t = array();
        $t['total'] = 0;
        $title = trim($this->req->jsonBody['title'] ?? '');
        $prio = 0;
        $tags = '';
        $duedate = null;
        if (Config::get('smartsyntax') != 0)
        {
            $a = parseSmartSyntax($title);
            if ($a === false) {
                return $t;
            }
            $title = (string)$a['title'];
            $prio = (int)$a['prio'];
            $tags = (string)$a['tags'];
            if (isset($a['duedate']) && preg_match("|^\d+-\d+-\d+$|", $a['duedate'])) {
                $duedate = $a['duedate'];
            }
        }
        if ($title == '') {
            return $t;
        }
        if (Config::get('autotag')) {
            $tags .= ',' . ($this->req->jsonBody['tag'] ?? '');
        }
        $ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=0");
        $date = time();
        $db->ex("BEGIN");
        $db->dq("INSERT INTO {$db->prefix}todolist (uuid,list_id,title,d_created,d_edited,ow,prio,duedate) VALUES (?,?,?,?,?,?,?,?)",
                    array(generateUUID(), $listId, $title, $date, $date, $ow, $prio, $duedate) );
        $id = (int) $db->lastInsertId();
        if ($tags != '')
        {
            $aTags = $this->prepareTags($tags);
            if ($aTags) {
                $this->addTaskTags($id, $aTags['ids'], $listId);
            }
        }
        $db->ex("COMMIT");
        $task = $this->getTaskRowById($id);
        MTTNotificationCenter::postNotification(MTTNotification::didCreateTask, $task);
        $t['list'][] = $task;
        $t['total'] = 1;
        return $t;
    }

    private function fullNewTaskInList(int $listId): ?array
    {
        $db = DBConnection::instance();
        $title = trim($this->req->jsonBody['title'] ?? '');
        $note = str_replace("\r\n", "\n", $this->req->jsonBody['note'] ?? '');
        $prio = (int)($this->req->jsonBody['prio'] ?? 0);
        if ($prio < -1) $prio = -1;
        elseif ($prio > 2) $prio = 2;
        $duedate = MTTSmartSyntax::parseDuedate(trim( $this->req->jsonBody['duedate'] ?? '' ));
        $t = array();
        $t['total'] = 0;
        if ($title == '') {
            return $t;
        }
        $tags = $this->req->jsonBody['tags'] ?? '';
        if (Config::get('autotag'))
            $tags .= ',' . ($this->req->jsonBody['tag'] ?? '');
        $ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=0");
        $date = time();
        $db->ex("BEGIN");
        $db->dq("INSERT INTO {$db->prefix}todolist (uuid,list_id,title,d_created,d_edited,ow,prio,note,duedate) VALUES (?,?,?,?,?,?,?,?,?)",
                    array(generateUUID(), $listId, $title, $date, $date, $ow, $prio, $note, $duedate) );
        $id = (int) $db->lastInsertId();
        if ($tags != '')
        {
            $aTags = $this->prepareTags($tags);
            if ($aTags) {
                $this->addTaskTags($id, $aTags['ids'], $listId);
            }
        }
        $db->ex("COMMIT");
        $task = $this->getTaskRowById($id);
        MTTNotificationCenter::postNotification(MTTNotification::didCreateTask, $task);
        $t['list'][] = $task;
        $t['total'] = 1;
        return $t;
    }

    private function editTask(int $id): ?array
    {
        $db = DBConnection::instance();
        $title = trim($this->req->jsonBody['title'] ?? '');
        $note = str_replace("\r\n", "\n", $this->req->jsonBody['note'] ?? '');
        $prio = (int)($this->req->jsonBody['prio'] ?? 0);
        if ($prio < -1) $prio = -1;
        elseif ($prio > 2) $prio = 2;
        $duedate = MTTSmartSyntax::parseDuedate(trim( $this->req->jsonBody['duedate'] ?? '' ));
        $t = array();
        $t['total'] = 0;
        if ($title == '') {
            return $t;
        }
        $listId = (int) $db->sq("SELECT list_id FROM {$db->prefix}todolist WHERE id=$id");
        $tags = trim( $this->req->jsonBody['tags'] ?? '' );
        $db->ex("BEGIN");
        $db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id=$id");
        $aTags = $this->prepareTags($tags);
        if ($aTags) {
            $this->addTaskTags($id, $aTags['ids'], $listId);
        }
        $db->dq("UPDATE {$db->prefix}todolist SET title=?,note=?,prio=?,duedate=?,d_edited=? WHERE id=$id",
                array($title, $note, $prio, $duedate, time()) );
        $db->ex("COMMIT");
        $task = $this->getTaskRowById($id);
        MTTNotificationCenter::postNotification(MTTNotification::didEditTask, ['task' => $task]);
        $t['list'][] = $task;
        $t['total'] = 1;
        return $t;
    }

    private function moveTask(int $id): ?array
    {
        $fromId = (int)($this->req->jsonBody['from'] ?? 0);
        $toId = (int)($this->req->jsonBody['to'] ?? 0);
        $result = $this->doMoveTask($id, $toId);
        if ($result && MTTNotificationCenter::hasObserversForNotification(MTTNotification::didEditTask)) {
            $task = $this->getTaskRowById($id);
            MTTNotificationCenter::postNotification(MTTNotification::didEditTask, [
                'property' => 'list',
                'task' => $task
            ]);
        }
        $t = array('total' => $result ? 1 : 0);
        if ($fromId == -1 && $result) {
            $t['list'][] = $this->getTaskRowById($id);
        }
        return $t;
    }

    private function doMoveTask(int $id, int $listId): bool
    {
        $db = DBConnection::instance();

        // Check task exists and not in target list
        $r = $db->sqa("SELECT * FROM {$db->prefix}todolist WHERE id=?", array($id));
        if (!$r || $listId == $r['list_id']) return false;

        // Check target list exists
        if (!$db->sq("SELECT COUNT(*) FROM {$db->prefix}lists WHERE id=?", [$listId]))
            return false;

        $ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=? AND compl=?", array($listId, $r['compl']?1:0));

        $db->ex("BEGIN");
        $db->ex("UPDATE {$db->prefix}tag2task SET list_id=? WHERE task_id=?", array($listId, $id));
        $db->dq("UPDATE {$db->prefix}todolist SET list_id=?, ow=?, d_edited=? WHERE id=?", array($listId, $ow, time(), $id));
        $db->ex("COMMIT");
        return true;
    }

    private function completeTask(int $id): ?array
    {
        $db = DBConnection::instance();
        $compl = (int)($this->req->jsonBody['compl'] ?? 0);
        $listId = (int)$db->sq("SELECT list_id FROM {$db->prefix}todolist WHERE id=$id");
        if ($compl) $ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=1");
        else $ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=0");
        $date = time();
        $dateCompleted = $compl ? $date : 0;
        $db->dq("UPDATE {$db->prefix}todolist SET compl=$compl,ow=$ow,d_completed=?,d_edited=? WHERE id=$id",
                    array($dateCompleted, $date) );
        $task = $this->getTaskRowById($id);
        MTTNotificationCenter::postNotification(MTTNotification::didCompleteTask, $task);
        $t = array();
        $t['total'] = 1;
        $t['list'][] = $task;
        return $t;
    }

    private function editNote(int $id): ?array
    {
        $db = DBConnection::instance();
        $note = $this->req->jsonBody['note'] ?? '';
        $note = str_replace("\r\n", "\n", $note);
        $db->dq("UPDATE {$db->prefix}todolist SET note=?,d_edited=? WHERE id=$id", array($note, time()) );
        if (MTTNotificationCenter::hasObserversForNotification(MTTNotification::didEditTask)) {
            $task = $this->getTaskRowById($id);
            MTTNotificationCenter::postNotification(MTTNotification::didEditTask, [
                'property' => 'note',
                'task' => $task
            ]);
        }
        $t = array();
        $t['total'] = 1;
        $t['list'][] = array('id'=>$id, 'note'=> noteMarkup($note), 'noteText'=>(string)$note);
        return $t;
    }

    private function priorityTask(int $id): ?array
    {
        $db = DBConnection::instance();
        $prio = (int)($this->req->jsonBody['prio'] ?? 0);
        if ($prio < -1) $prio = -1;
        elseif ($prio > 2) $prio = 2;
        $db->ex("UPDATE {$db->prefix}todolist SET prio=$prio,d_edited=? WHERE id=$id", array(time()) );
        if (MTTNotificationCenter::hasObserversForNotification(MTTNotification::didEditTask)) {
            $task = $this->getTaskRowById($id);
            MTTNotificationCenter::postNotification(MTTNotification::didEditTask, [
                'property' => 'priority',
                'task' => $task
            ]);
        }
        $t = array();
        $t['total'] = 1;
        $t['list'][] = array('id'=>$id, 'prio'=>$prio);
        return $t;
    }


    private function changeTaskOrder(): ?array
    {
        $db = DBConnection::instance();
        $order = $this->req->jsonBody['order'] ?? null;
        $t = array();
        $t['total'] = 0;
        if (is_array($order))
        {
            $ad = array();
            foreach ($order as $obj) {
                $id = $obj['id'] ?? 0;
                $diff = $obj['diff'] ?? 0;
                $ad[(int)$diff][] = (int)$id;
            }
            $db->ex("BEGIN");
            foreach ($ad as $diff=>$ids) {
                if ($diff >=0) $set = "ow=ow+".$diff;
                else $set = "ow=ow-".abs($diff);
                $db->dq("UPDATE {$db->prefix}todolist SET $set,d_edited=? WHERE id IN (".implode(',',$ids).")", array(time()) );
            }
            $db->ex("COMMIT");
            $t['total'] = 1;
        }
        return $t;
    }

    private function deleteTask(int $id)
    {
        $id = (int)$id;
        $task = null;
        if (MTTNotificationCenter::hasObserversForNotification(MTTNotification::didDeleteTask)) {
            $task = $this->getTaskRowById($id);
        }
        $db = DBConnection::instance();
        $db->ex("BEGIN");
        $db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id=$id");
        //TODO: delete unused tags?
        $db->dq("DELETE FROM {$db->prefix}todolist WHERE id=$id");
        $deleted = $db->affected();
        $db->ex("COMMIT");
        if ($deleted && MTTNotificationCenter::hasObserversForNotification(MTTNotification::didDeleteTask)) {
            MTTNotificationCenter::postNotification(MTTNotification::didDeleteTask, $task);
        }
        $t = array();
        $t['total'] = $deleted;
        $t['list'][] = array('id' => $id);
        return $t;
    }

    private function getUserListsSimple(bool $readOnly = false): array
    {
        $db = DBConnection::instance();
        $sqlWhere = '';
        if ($readOnly) {
            $sqlWhere = "WHERE published=1";
        }
        $a = array();
        $q = $db->dq("SELECT id,name FROM {$db->prefix}lists $sqlWhere ORDER BY id ASC");
        while($r = $q->fetchRow()) {
            $a[ (string)$r[0] ] = (string)$r[1];
        }
        return $a;
    }

    private function getTaskRowById(int $id): ?array
    {
        $r = DBCore::default()->getTaskById($id);
        if (!$r) {
            throw new Exception("Failed to fetch task data");
        }
        return $this->prepareTaskRow($r);
    }

    private function prepareTaskRow(array $r): array
    {
        $lang = Lang::instance();
        $dueA = $this->prepareDuedate($r['duedate']);
        $dCreated = timestampToDatetime($r['d_created']);
        $isEdited = ($r['d_edited'] != $r['d_created']);
        $dEdited = $isEdited ? timestampToDatetime($r['d_edited']) : '';
        $dCompleted = $r['d_completed'] ? timestampToDatetime($r['d_completed']) : '';
        if (!Config::get('showtime')) {
            $dCreatedFull = timestampToDatetime($r['d_created'], true);
            $dEditedFull = $isEdited ? timestampToDatetime($r['d_edited'], true) : '';
            $dCompletedFull = $r['d_completed'] ? timestampToDatetime($r['d_completed'], true) : '';
        }
        else {
            $dCreatedFull = $dCreated;
            $dEditedFull = $dEdited;
            $dCompletedFull = $dCompleted;
        }

        return array(
            'id' => $r['id'],
            'title' => titleMarkup( $r['title'] ),
            'titleText' => (string)$r['title'],
            'listId' => $r['list_id'],
            'listName' => htmlarray($r['list_name'] ?? ''),
            'date' => htmlarray($dCreated),
            'dateInt' => (int)$r['d_created'],
            'dateFull' => htmlarray($dCreatedFull),
            'dateInlineTitle' => htmlarray(sprintf($lang->get('taskdate_inline_created'), $dCreated)), //TODO: move preparing of *inlineTitle to js
            'dateEdited' => htmlarray($dEdited),
            'dateEditedInt' => (int)$r['d_edited'],
            'dateEditedFull' => htmlarray($dEditedFull),
            'dateEditedInlineTitle' => htmlarray(sprintf($lang->get('taskdate_inline_edited'), $dEdited)),
            'isEdited' => (bool)$isEdited,
            'dateCompleted' => htmlarray($dCompleted),
            'dateCompletedFull' => htmlarray($dCompletedFull),
            'dateCompletedInlineTitle' => htmlarray(sprintf($lang->get('taskdate_inline_completed'), $dCompleted)),
            'compl' => (int)$r['compl'],
            'prio' => $r['prio'],
            'note' => noteMarkup($r['note']),
            'noteText' => (string)$r['note'],
            'ow' => (int)$r['ow'],
            'tags' => htmlarray($r['tags'] ?? ''),
            'tags_ids' => htmlarray($r['tags_ids'] ?? ''),
            'duedate' => htmlarray($dueA['formatted']),
            'dueClass' => $dueA['class'],
            'dueStr' => htmlarray($dueA['str']),
            'dueInt' => $this->date2int($r['duedate']),
            'dueTitle' => htmlarray(sprintf($lang->get('taskdate_inline_duedate'), $dueA['formattedlong'])),
        );
    }

    private function prepareDuedate($duedate): array
    {
        $lang = Lang::instance();

        $a = array( 'class'=>'', 'str'=>'', 'formatted'=>'', 'formattedlong'=>'', 'timestamp'=>0 );
        if ($duedate == '') {
            return $a;
        }
        $ad = explode('-', $duedate);
        $y = (int)$ad[0];
        $m = (int)$ad[1];
        $d = (int)$ad[2];
        $a['timestamp'] = mktime(0, 0, 0, $m, $d, $y);

        $oToday = new DateTimeImmutable(date("Y-m-d"));
        $oDue = new DateTimeImmutable($duedate);
        $oDiff = $oToday->diff($oDue);
        if ($oDiff === false) {
            return $a;
        }
        $thisYear = ((int)$oToday->format('Y') == $y);
        $days = $oDiff->days;
        if ($oDiff->invert) $days *= -1;

        $exact = Config::get('exactduedate') ? true : false;

        if ($days < -7 && !$thisYear) {
            $a['class'] = 'past';
            $a['str'] = formatDate3(Config::get('dateformat2'), $y, $m, $d, $lang);
        }
        elseif ($days < -7) {
            $a['class'] = 'past';
            $a['str'] = formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($days < -1) {
             $a['class'] = 'past';
             $a['str'] = !$exact ? sprintf($lang->get('daysago'), abs($days)) : formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($days == -1)  {
             $a['class'] = 'past';
             $a['str'] = !$exact ? $lang->get('yesterday') : formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($days == 0) {
            $a['class'] = 'today';
            $a['str'] = !$exact ? $lang->get('today') : formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($days == 1) {
            $a['class'] = 'today';
            $a['str'] = !$exact ? $lang->get('tomorrow') : formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($days <= 7) {
            $a['class'] = 'soon';
            $a['str'] = !$exact ? sprintf($lang->get('indays'), $days) : formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($thisYear) {
            $a['class'] = 'future';
            $a['str'] = formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        else {
            $a['class'] = 'future';
            $a['str'] = formatDate3(Config::get('dateformat2'), $y, $m, $d, $lang);
        }

        #avoid short year
        $fmt = str_replace('y', 'Y', Config::get('dateformat2'));
        $a['formatted'] = formatTime($fmt, $a['timestamp']);
        $a['formattedlong'] = formatTime(Config::get('dateformat'), $a['timestamp']);

        return $a;
    }

    private function date2int($d) : int
    {
        if (!$d) {
            return 33330000;
        }
        $ad = explode('-', $d);
        $s = $ad[0];
        if (strlen($ad[1]) < 2) $s .= "0$ad[1]"; else $s .= $ad[1];
        if (strlen($ad[2]) < 2) $s .= "0$ad[2]"; else $s .= $ad[2];
        return (int)$s;
    }

    private function getTagId($tag)
    {
        $db = DBConnection::instance();
        $id = $db->sq("SELECT id FROM {$db->prefix}tags WHERE name=?", array($tag));
        return $id ? $id : 0;
    }

    private function getOrCreateTag($name): array
    {
        $db = DBConnection::instance();
        $tagId = $db->sq("SELECT id FROM {$db->prefix}tags WHERE name=?", array($name));
        if ($tagId)
            return array('id'=>$tagId, 'name'=>$name);

        $db->ex("INSERT INTO {$db->prefix}tags (name) VALUES (?)", array($name));
        return array(
            'id' => $db->lastInsertId(),
            'name' => $name
        );
    }

    private function prepareTags(string $tagsStr): ?array
    {
        $tags = explode(',', $tagsStr);
        if (!$tags) return null;

        $aTags = array('tags'=>array(), 'ids'=>array());
        foreach ($tags as $tag)
        {
            $tag = str_replace(array('^','#'),'',trim($tag));
            if ($tag == '') continue;

            $aTag = $this->getOrCreateTag($tag);
            if ($aTag && !in_array($aTag['id'], $aTags['ids'])) {
                $aTags['tags'][] = $aTag['name'];
                $aTags['ids'][] = $aTag['id'];
            }
        }
        return $aTags;
    }

    private function addTaskTags(int $taskId, array $tagIds, int $listId)
    {
        $db = DBConnection::instance();
        if (!$tagIds) return;
        foreach ($tagIds as $tagId) {
            $db->ex(
                "INSERT INTO {$db->prefix}tag2task (task_id,tag_id,list_id) VALUES (?,?,?)",
                array($taskId, $tagId, $listId)
            );
        }
    }



}
