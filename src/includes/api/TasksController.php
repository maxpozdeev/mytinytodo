<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

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

        $sqlWhere = $sqlWhereListId = $sqlHaving = '';
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
                if ($atv == '')
                    continue;
                // tasks without tags (ignore other tags included or excluded)
                if ($atv == '^') {
                    $tagIds = [];
                    $tagExIds = [];
                    if ($db::DBTYPE == DBConnection::DBTYPE_MYSQL)
                        $sqlHaving = "tags_ids = ''";
                    else
                        $sqlHaving = "string_agg(tags.name, ',') IS NULL"; // catches if tag name is ''
                    break;
                }
                // tasks with any tag
                else if ($atv == '^^') {
                    if ($db::DBTYPE == DBConnection::DBTYPE_MYSQL)
                        $sqlHaving = "tags_ids != ''";
                    else
                        $sqlHaving = "string_agg(tags.name, ',') != ''";
                }
                else if (substr($atv,0,1) == '^') {
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
        if ($sqlHaving != '')
            $sqlHaving = "HAVING $sqlHaving";

        $q = $db->dq("
            SELECT todo.*, todo.duedate IS NULL AS ddn, $groupConcat
            FROM {$db->prefix}todolist AS todo
            LEFT JOIN {$db->prefix}tag2task AS t2t ON todo.id = t2t.task_id
            LEFT JOIN {$db->prefix}tags AS tags ON t2t.tag_id = tags.id
            WHERE $sqlWhereListId $sqlWhere
            GROUP BY todo.id   $sqlHaving
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
        $id = (int)$id;
        $repo = new TaskRepo(DBConnection::instance());
        $task = $repo->findTaskById($id);
        if (!$task) {
            return $this->response->errorJsonContent(__("taskNotFound"), 404);
        }
        checkWriteAccess($task->listId);
        $this->response->data = $this->deleteTask($task);
    }

    /**
     * Edit some properties of Task
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    function putId($id)
    {
        $id = (int)$id;
        $repo = new TaskRepo(DBConnection::instance());
        $task = $repo->findTaskById($id);
        if (!$task) {
            return $this->response->errorJsonContent(__("taskNotFound"), 404);
        }
        checkWriteAccess($task->listId);

        $action = $this->req->jsonBody['action'] ?? '';
        switch ($action) {
            case 'edit':     $this->response->data = $this->editTask($id);     break;
            case 'complete': $this->response->data = $this->completeTask($task); break;
            case 'note':     $this->response->data = $this->editNote($task);     break;
            case 'move':     $this->response->data = $this->moveTask($task);     break;
            case 'priority': $this->response->data = $this->priorityTask($task); break;
            case 'delete':   $this->response->data = $this->deleteTask($task);   break; //compatibility
            default:         $this->response->data = [
                'ok' => false,
                'total' => 0,
                'error' => "Unexpected action",
            ];
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
                $dueA = Task::prepareDuedate($a['duedate']);
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
        $time = time();

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
            'lists' => $a,
            'time' => $time
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
            $aTags = $this->prepareTags($this->req->userId(), $tags);
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
            $aTags = $this->prepareTags($this->req->userId(), $tags);
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
        $aTags = $this->prepareTags($this->req->userId(), $tags);
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


    private function moveTask(Task $task): ?array
    {
        #$fromId = (int)($this->req->jsonBody['from'] ?? 0);
        $toId = (int)($this->req->jsonBody['to'] ?? 0);

        $failedResult = [
            'ok' => false,
            'total' => 0,
            'error' => "Failed to move a task",
        ];

        if ($task->listId == $toId) {
            return $failedResult;
        }
        $list = (new ListRepo(DBConnection::instance()))->findListById($toId);
        if (!$list) {
            return $failedResult;
        }

        if ($task->setList($list)) {
            $repo = new TaskRepo(DBConnection::instance());
            $repo->updateTaskProperties($task);
            MTTNotificationCenter::postNotification(MTTNotification::didEditTask, [
                'property' => 'list',
                'task' => $task
            ]);
        }

        return [
            'ok' => true,
            'total' => 1,
            'list' => [ $task->toJsonApiArray() ]
        ];
    }


    private function completeTask(Task $task): ?array
    {
        $compl = boolval($this->req->jsonBody['compl'] ?? 0);
        if ($task->setIsCompleted($compl)) {
            $repo = new TaskRepo(DBConnection::instance());
            $repo->updateTaskProperties($task);
            MTTNotificationCenter::postNotification(MTTNotification::didCompleteTask, $task);
        }

        return [
            'ok' => true,
            'total' => 1,
            'list' => [ $task->toJsonApiArray() ],
        ];
    }


    private function editNote(Task $task): ?array
    {
        $note = $this->req->jsonBody['note'] ?? '';
        if ($task->setNote($note)) {
            $repo = new TaskRepo(DBConnection::instance());
            $repo->updateTaskProperties($task);
            MTTNotificationCenter::postNotification(MTTNotification::didEditTask, [
                'property' => 'note',
                'task' => $task
            ]);
        }
        return [
            'ok' => true,
            'total' => 1,
            'list' => [ $task->toJsonApiArray() ],
        ];
    }

    private function priorityTask(Task $task): ?array
    {
        $prio = (int)($this->req->jsonBody['prio'] ?? 0);
        if ($prio < -1)
            $prio = -1;
        elseif ($prio > 2)
            $prio = 2;

        if ($task->setPriority($prio)) {
            $repo = new TaskRepo(DBConnection::instance());
            $repo->updateTaskProperties($task);
            MTTNotificationCenter::postNotification(MTTNotification::didEditTask, [
                'property' => 'priority',
                'task' => $task
            ]);
        }
        return [
            'ok' => true,
            'total' => 1,
            'list' => [ $task->toJsonApiArray() ]
        ];
    }


    private function changeTaskOrder(): ?array
    {
        $order = $this->req->jsonBody['order'] ?? null;

        if (!is_array($order)) {
            return [
                'ok' => false,
                'total' => 0,
                'error' => "Unexpected type of order"
            ];
        }

        $repo = new TaskRepo(DBConnection::instance());
        $repo->changeTaskOrder($order);
        return [
            'ok' => true,
            'total' => 1,
        ];
    }


    private function deleteTask(Task $task)
    {
        $repo = new TaskRepo(DBConnection::instance());
        $t = [
            'ok' => true,
            'total' => $repo->deleteTaskById($task->id),
            'list' => [ array('id' => $task->id) ]
        ];
        if ($t['total']) {
            MTTNotificationCenter::postNotification(MTTNotification::didDeleteTask, $task);
        }
        return $t;
    }

    private function getUserListsSimple(bool $readOnly = false): array
    {
        $db = DBConnection::instance();
        $sqlWhere = 'WHERE user_id='. $this->req->userId();
        if ($readOnly) {
            $sqlWhere .= " AND published=1";
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
        $dueA = Task::prepareDuedate($r['duedate']);
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

    private function getOrCreateTag(int $userId, $name): array
    {
        $db = DBConnection::instance();
        $tagId = $db->sq("SELECT id FROM {$db->prefix}tags WHERE user_id=? AND name=?", array($userId, $name));
        if ($tagId)
            return array('id'=>$tagId, 'name'=>$name);

        $db->ex("INSERT INTO {$db->prefix}tags (user_id,name) VALUES (?,?)", array($userId, $name));
        return array(
            'id' => $db->lastInsertId(),
            'name' => $name
        );
    }

    private function prepareTags(int $userId, string $tagsStr): ?array
    {
        $tags = explode(',', $tagsStr);
        if (!$tags) return null;

        $aTags = array('tags'=>array(), 'ids'=>array());
        foreach ($tags as $tag)
        {
            $tag = str_replace(array('^','#'),'',trim($tag));
            if ($tag == '') continue;

            $aTag = $this->getOrCreateTag($userId, $tag);
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
