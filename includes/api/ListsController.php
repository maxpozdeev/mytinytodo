<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class ListsController extends ApiController {

    /**
     * Get all lists
     * @return void
     * @throws Exception
     */
    function get()
    {
        $db = DBConnection::instance();
        check_token();
        $t = array();
        $t['total'] = 0;
        $haveWriteAccess = haveWriteAccess();
        if (!$haveWriteAccess) {
            $sqlWhere = 'WHERE published=1';
        }
        else {
            $sqlWhere = '';
            $t['list'][] = $this->prepareAllTasksList(); // show alltasks lists only for authorized user
            $t['total'] = 1;
        }
        $t['time'] = time();
        $q = $db->dq("SELECT * FROM {$db->prefix}lists $sqlWhere ORDER BY ow ASC, id ASC");
        while ($r = $q->fetchAssoc())
        {
            $t['total']++;
            $t['list'][] = $this->prepareList($r, $haveWriteAccess);
        }
        $this->response->data = $t;
    }


    /**
     * Create new list and Actions with all lists
     * Code 201 on success
     * @return void
     * @throws Exception
     */
    function post()
    {
        checkWriteAccess();
        $action = $this->req->jsonBody['action'] ?? '';
        switch ($action) {
            case 'order': $this->response->data = $this->changeListOrder(); break; //compatibility
            case 'new':
            default:      $this->response->data = $this->createList();
        }
    }

    /**
     * Actions with all lists
     * @return void
     * @throws Exception
     */
    function put()
    {
        checkWriteAccess();
        $action = $this->req->jsonBody['action'] ?? '';
        switch ($action) {
            case 'order': $this->response->data = $this->changeListOrder(); break;
            default:      $this->response->data = ['total' => 0]; // error 400 ?
        }
    }


    /* Single list */

    /**
     * Get single list by Id
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    function getId($id)
    {
        checkReadAccess($id);
        $db = DBConnection::instance();
        $r = $db->sqa( "SELECT * FROM {$db->prefix}lists WHERE id=?", array($id) );
        if (!$r) {
            $this->response->data = null;
            return;
        }
        $t = $this->prepareList($r, haveWriteAccess());
        $this->response->data = $t;
    }

    /**
     * Delete list by Id
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    function deleteId($id)
    {
        checkWriteAccess();
        $this->response->data = $this->deleteList($id);
    }


    /**
     * Edit some properties of List
     * Actions: rename, ...
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    function putId($id)
    {
        checkWriteAccess();
        $id = (int)$id;

        $action = $this->req->jsonBody['action'] ?? '';
        switch ($action) {
            case 'rename':         $this->response->data = $this->renameList($id);     break;
            case 'sort':           $this->response->data = $this->sortList($id);       break;
            case 'publish':        $this->response->data = $this->publishList($id);    break;
            case 'enableFeedKey':  $this->response->data = $this->enableFeedKey($id);  break;
            case 'showNotes':      $this->response->data = $this->showNotes($id);      break;
            case 'hide':           $this->response->data = $this->hideList($id);       break;
            case 'clearCompleted': $this->response->data = $this->clearCompleted($id); break;
            case 'delete':         $this->response->data = $this->deleteList($id);     break; //compatibility
            default:               $this->response->data = ['total' => 0];
        }
    }


    /* Private Functions */

    private function prepareAllTasksList(): array
    {
        //default values
        $hidden = 1;
        $sort = 3;
        $showCompleted = 1;

        $opts = Config::requestDomain('alltasks.json');
        if ( isset($opts['hidden']) ) $hidden = (int)$opts['hidden'] ? 1 : 0;
        if ( isset($opts['sort']) ) $sort = (int)$opts['sort'];
        if ( isset($opts['showCompleted']) ) $showCompleted = (int)$opts['showCompleted'];

        return array(
            'id' => -1,
            'name' => htmlarray(__('alltasks')),
            'sort' => $sort,
            'published' => 0,
            'showCompl' => $showCompleted,
            'showNotes' => 0,
            'hidden' => $hidden,
            'feedKey' => '',
        );
    }

    private function getListRowById(int $id)
    {
        $r = DBCore::default()->getListById($id);
        if (!$r) {
            throw new Exception("Failed to fetch list data");
        }
        return $this->prepareList($r, true);
    }

    private function prepareList($row, bool $haveWriteAccess): array
    {
        $taskview = (int)$row['taskview'];
        $feedKey = '';
        if ($haveWriteAccess) {
            $extra = json_decode($row['extra'] ?? '', true, 10, JSON_INVALID_UTF8_SUBSTITUTE);
            if ($extra === false) {
                error_log("Failed to decodes JSON data of list extra listId=". (int)$row['id'] . ": " . json_last_error_msg());
                $extra = [];
            }
            $feedKey = (string) ($extra['feedKey'] ?? '');
        }

        return array(
            'id' => $row['id'],
            'name' => htmlarray($row['name']),
            'sort' => (int)$row['sorting'],
            'published' => $row['published'] ? 1 :0,
            'showCompl' => $taskview & 1 ? 1 : 0,
            'showNotes' => $taskview & 2 ? 1 : 0,
            'hidden' => $taskview & 4 ? 1 : 0,
            'feedKey' => $feedKey,
        );
    }

    private function createList(): ?array
    {
        $t = array();
        $t['total'] = 0;
        $id = DBCore::default()->createListWithName($this->req->jsonBody['name'] ?? '');
        if (!$id) {
            return $t;
        }
        $db = DBConnection::instance();
        $t['total'] = 1;
        $r = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$id");
        $oo = $this->prepareList($r, true);
        MTTNotificationCenter::postNotification(MTTNotification::didCreateList, $oo);
        $t['list'][] = $oo;
        return $t;
    }

    private function renameList(int $id): ?array
    {
        $db = DBConnection::instance();
        $t = array();
        $t['total'] = 0;
        $name = str_replace(
            array('"',"'",'<','>','&'),
            array('','','','',''),
            trim($this->req->jsonBody['name'] ?? '')
        );
        $db->dq("UPDATE {$db->prefix}lists SET name=?,d_edited=? WHERE id=$id", array($name, time()) );
        $t['total'] = $db->affected();
        $r = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$id");
        $t['list'][] = $this->prepareList($r, true);
        return $t;
    }

    private function sortList(int $listId): ?array
    {
        $sort = (int)($this->req->jsonBody['sort'] ?? 0);
        self::setListSortingById($listId, $sort);
        return ['total'=>1];
    }

    static function setListSortingById(int $listId, int $sort)
    {
        $db = DBConnection::instance();
        if ($sort < 0 || ($sort > 5 && $sort < 100) || $sort > 105) {
            $sort = 0;
        }
        if ($listId == -1) {
            $opts = Config::requestDomain('alltasks.json');
            $opts['sort'] = $sort;
            Config::saveDomain('alltasks.json', $opts);
        }
        else {
            $db->ex("UPDATE {$db->prefix}lists SET sorting=$sort,d_edited=? WHERE id=$listId", array(time()));
        }
    }

    static function setListShowCompletedById(int $listId, bool $showCompleted)
    {
        $db = DBConnection::instance();
        if ($listId == -1) {
            $opts = Config::requestDomain('alltasks.json');
            $opts['showCompleted'] = (int)$showCompleted;
            Config::saveDomain('alltasks.json', $opts);
        }
        else {
            $bitwise = $showCompleted ? 'taskview | 1' : 'taskview & ~1';
            $db->dq("UPDATE {$db->prefix}lists SET taskview=$bitwise WHERE id=?", [$listId]);
        }
    }

    private function publishList(int $listId): ?array
    {
        $db = DBConnection::instance();
        $publish = (int)($this->req->jsonBody['publish'] ?? 0);
        $db->ex("UPDATE {$db->prefix}lists SET published=?,d_edited=? WHERE id=$listId", array($publish ? 1 : 0, time()));
        return ['total'=>1];
    }

    private function enableFeedKey(int $listId): ?array
    {
        $db = DBConnection::instance();
        $flag = (int)($this->req->jsonBody['enable'] ?? 0);
        $json = $db->sq("SELECT extra FROM {$db->prefix}lists WHERE id=$listId") ?? '';
        $extra = strlen($json) > 0 ? json_decode($json, true, 10, JSON_INVALID_UTF8_SUBSTITUTE) : [];
        if ($extra === false) {
            error_log("Failed to decodes JSON data of list extra listId=$listId: " . json_last_error_msg());
            $extra = [];
        }
        if ($flag == 0) {
            $extra['feedKey'] = '';
        }
        else {
            $extra['feedKey'] = randomString();
        }
        $json = json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $db->ex("UPDATE {$db->prefix}lists SET extra=?,d_edited=? WHERE id=$listId", array($json, time()));
        return [
            'total' => 1,
            'list' => [[
                'id' => $listId,
                'feedKey' => $extra['feedKey']
            ]]
        ];
    }

    private function showNotes(int $listId): ?array
    {
        $db = DBConnection::instance();
        $flag = (int)($this->req->jsonBody['shownotes'] ?? 0);
        $bitwise = ($flag == 0) ? 'taskview & ~2' : 'taskview | 2';
        $db->dq("UPDATE {$db->prefix}lists SET taskview=$bitwise WHERE id=$listId");
        return ['total'=>1];
    }

    private function hideList(int $listId): ?array
    {
        $db = DBConnection::instance();
        $flag = (int)($this->req->jsonBody['hide'] ?? 0);
        if ($listId == -1) {
            $opts = Config::requestDomain('alltasks.json');
            $opts['hidden'] = $flag ? 1 : 0;
            Config::saveDomain('alltasks.json', $opts);
        }
        else {
            $bitwise = ($flag == 0) ? 'taskview & ~4' : 'taskview | 4';
            $db->dq("UPDATE {$db->prefix}lists SET taskview=$bitwise WHERE id=$listId");
        }
        return ['total'=>1];
    }

    private function clearCompleted(int $listId): ?array
    {
        $db = DBConnection::instance();
        $t = array();
        $t['total'] = 0;
        $db->ex("BEGIN");
        $db->ex("DELETE FROM {$db->prefix}tag2task WHERE task_id IN (SELECT id FROM {$db->prefix}todolist WHERE list_id=? and compl=1)", array($listId));
        $db->ex("DELETE FROM {$db->prefix}todolist WHERE list_id=$listId and compl=1");
        $t['total'] = $db->affected();
        $db->ex("COMMIT");
        if (MTTNotificationCenter::hasObserversForNotification(MTTNotification::didDeleteCompletedInList)) {
            $list = $this->getListRowById($listId);
            MTTNotificationCenter::postNotification(MTTNotification::didDeleteCompletedInList, [
                'total' => $t['total'],
                'list' => $list
            ]);
        }
        return $t;
    }

    private function changeListOrder(): ?array
    {
        $t = array();
        $t['total'] = 0;
        if (!is_array($this->req->jsonBody['order'])) {
            return $t;
        }
        $db = DBConnection::instance();
        $order = $this->req->jsonBody['order'];
        $a = array();
        $setCase = '';
        foreach ($order as $ow => $id) {
            $id = (int)$id;
            $a[] = $id;
            $setCase .= "WHEN id=$id THEN $ow\n";
        }
        $ids = implode(',', $a);
        $db->dq("UPDATE {$db->prefix}lists SET d_edited=?, ow = CASE\n $setCase END WHERE id IN ($ids)",
                    array(time()) );
        $t['total'] = 1;
        return $t;
    }

    private function deleteList(int $id)
    {
        $db = DBConnection::instance();
        $t = array();
        $t['total'] = 0;
        $id = (int)$id;
        $list = null;
        if (MTTNotificationCenter::hasObserversForNotification(MTTNotification::didDeleteList)) {
            $list = $this->getListRowById($id);
        }
        $db->ex("BEGIN");
        $db->ex("DELETE FROM {$db->prefix}lists WHERE id=$id");
        $t['total'] = $db->affected();
        if ($t['total']) {
            $db->ex("DELETE FROM {$db->prefix}tag2task WHERE list_id=$id");
            $db->ex("DELETE FROM {$db->prefix}todolist WHERE list_id=$id");
        }
        $db->ex("COMMIT");
        if ($t['total'] && MTTNotificationCenter::hasObserversForNotification(MTTNotification::didDeleteList)) {
            MTTNotificationCenter::postNotification(MTTNotification::didDeleteList, $list);
        }
        return $t;
    }
}
