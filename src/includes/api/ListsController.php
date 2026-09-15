<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class ListsController extends ApiController {

    /**
     * Get all lists
     * @return void
     * @throws Exception
     */
    function get($username = null): void
    {
        $db = DBConnection::instance();
        if (!is_null($username) && $username !== '') {
            if (isset(MTTVars::$username) && $username === MTTVars::$username)
                $userId = userId();
            else
                $userId = (new UserRepo($db))->findUserIdByUsername($username);

            if (!$userId) {
                $this->response->errorJsonContent("User not found", 404);
                return;
            }
            if (!need_auth() && $userId !== userId()) {
                $this->response->errorJsonContent("User not found", 404);
                return;
            }
            $this->req->setUserId($userId); //set before haveWriteAccess
        }

        $isOwner = haveWriteAccess();

        $repo = new ListRepo($db);
        $t = [
            'time' => time(),
        ];
        if ($isOwner)
            $lists = $repo->findListsByUserId($this->req->userId(), true);
        else
            $lists = $repo->findPublicListsByUserId($this->req->userId());

        $t['total'] = count($lists);

        foreach ($lists as $list) {
            if ($isOwner)
                $t['list'][] = $list->toJsonApiArray();
            else
                $t['list'][] = $list->toPublicJsonApiArray();
        }
        $this->response->data = $t;
    }


    /**
     * Create new list and Actions with all lists
     * Code 201 on success
     * @return void
     * @throws Exception
     */
    function post(): void
    {
        checkWriteAccess();
        $action = $this->req->jsonBody['action'] ?? '';
        switch ($action) {
            case 'order': $this->response->data = $this->changeListOrder(userId()); break; //compatibility
            case 'new':
            default:      $this->response->data = $this->createList(userId());
        }
    }

    /**
     * Actions with all lists
     * @return void
     * @throws Exception
     */
    function put(): void
    {
        checkWriteAccess();
        $action = $this->req->jsonBody['action'] ?? '';
        switch ($action) {
            case 'order': $this->response->data = $this->changeListOrder(userId()); break;
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
    function getId($id): void
    {
        $id = (int)$id;
        $repo = new ListRepo(DBConnection::instance());
        $list = $repo->findRealListById($id);
        if (!$list || !canReadList($list)) {
            $this->response->errorJsonContent(__("listNotFound"), 404);
            return;
        }

        $isOwner = canWriteToList($list);
        if ($isOwner)
            $this->response->data = $list->toJsonApiArray();
        else
            $this->response->data = $list->toPublicJsonApiArray();
    }

    /**
     * Delete list by Id
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    function deleteId($id): void
    {
        $list = checkAndGetListForWrite((int)$id);
        $this->response->data = $this->deleteList($list);
    }


    /**
     * Edit some properties of List
     * Actions: rename, ...
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    function putId($id): void
    {
        $id = (int)$id;
        $list = checkAndGetListForWrite((int)$id);

        $action = $this->req->jsonBody['action'] ?? '';
        switch ($action) {
            case 'rename':         $this->response->data = $this->renameList($list);     break;
            case 'sort':           $this->response->data = $this->sortList($list);       break;
            case 'publish':        $this->response->data = $this->publishList($list);    break;
            case 'enableFeedKey':  $this->response->data = $this->enableFeedKey($list);  break;
            case 'showNotes':      $this->response->data = $this->showNotes($list);      break;
            case 'hide':           $this->response->data = $this->hideList($list);       break;
            case 'clearCompleted': $this->response->data = $this->clearCompleted($list); break;
            case 'delete':         $this->response->data = $this->deleteList($list);     break; //compatibility
            default:               $this->response->errorJsonContent("Unexpected action", 400); return;
        }
    }


    /* Private Functions */

    //TODO: rewrite
    private function createList(int $userId): ?array
    {
        $repo = new ListRepo(DBConnection::instance());
        $id = $repo->createList($this->req->jsonBody['name'] ?? '', $userId);
        if (!$id)
            return ['ok'=>false, 'total'=>0]; //error 400?

        $list = $repo->findRealListById($id);
        if (!$list)
            return ['ok'=>false, 'total'=>0]; //error 500?
        MTTNotificationCenter::postNotification(MTTNotification::didCreateList, $list);

        return [
            'ok' => true,
            'total' => 1,
            'list' => [$list->toJsonApiArray()]
        ];
    }

    private function renameList(TaskList $list): ?array
    {
        $list->setName( trim($this->req->jsonBody['name'] ?? '') );
        $repo = new ListRepo(DBConnection::instance());
        return [
            'ok' => true,
            'total' => $repo->updateListProperties($list),
            'list' => [$list->toJsonApiArray()],
        ];
    }


    private function sortList(AbstractTaskList $list): ?array
    {
        $sort = (int)($this->req->jsonBody['sort'] ?? 0);
        $list->setSort($sort);
        $repo = new ListRepo(DBConnection::instance());
        if ($list instanceof AlltasksList)
            $repo->updateAlltasksList($list);
        else
            $repo->updateListProperties($list);
        return [
            'ok' => true,
            'total' => 1,
        ];
    }

    private function publishList(TaskList $list): ?array
    {
        $publish = boolval($this->req->jsonBody['publish'] ?? 0);
        $list->setIsPublished($publish);
        $repo = new ListRepo(DBConnection::instance());
        $repo->updateListProperties($list);
        return [
            'ok' => true,
            'total' => 1,
        ];
    }

    private function enableFeedKey(TaskList $list): ?array
    {
        $flag = !!(int)($this->req->jsonBody['enable'] ?? 0);
        if ($flag)
            $list->setFeedKey(randomString());
        else
            $list->setFeedKey('');

        $repo = new ListRepo(DBConnection::instance());
        $repo->updateListProperties($list);

        return [
            'ok' => true,
            'total' => 1,
            'list' => [[
                'id' => $list->id,
                'feedKey' => $list->extra['feedKey'] ?? '',
            ]]
        ];
    }

    private function showNotes(TaskList $list): ?array
    {
        $flag = !!(int)($this->req->jsonBody['shownotes'] ?? 0);
        $list->setIsShowNotes($flag);
        $repo = new ListRepo(DBConnection::instance());
        $repo->updateListProperties($list);
        return [
            'ok' => true,
            'total' => 1,
        ];
    }

    private function hideList(AbstractTaskList $list): ?array
    {
        $flag = !!(int)($this->req->jsonBody['hide'] ?? 0);
        $list->setIsHidden($flag);

        $repo = new ListRepo(DBConnection::instance());
        if ($list instanceof AlltasksList)
            $repo->updateAlltasksList($list);
        else
            $repo->updateListProperties($list);
        return [
            'ok' => true,
            'total' => 1,
        ];
    }

    private function clearCompleted(TaskList $list): ?array
    {
        $repo = new ListRepo(DBConnection::instance());
        $t = [
            'ok' => true,
            'total' => $repo->deleteCompletedTasksInList($list->id)
        ];
        if ($t['total']) {
            MTTNotificationCenter::postNotification(MTTNotification::didDeleteCompletedInList, [
                'total' => $t['total'],
                'list' => $list
            ]);
        }
        return $t;
    }

    private function changeListOrder(int $userId): array
    {
        $order = $this->req->jsonBody['order'] ?? null;
        if (!$order || !is_array($order) || !array_is_list($order)) {
            return ['ok'=>false, 'total'=>0]; //error 400?
        }
        $repo = new ListRepo(DBConnection::instance());
        $repo->updateListOrderOfUser($order, $userId);
        return ['ok'=>true, 'total'=>1];
    }

    private function deleteList(TaskList $list)
    {
        $repo = new ListRepo(DBConnection::instance());
        $t = [
            'ok' => true,
            'total' => $repo->deleteListById($list->id)
        ];
        if ($t['total']) {
            MTTNotificationCenter::postNotification(MTTNotification::didDeleteList, $list);
        }
        return $t;
    }
}
