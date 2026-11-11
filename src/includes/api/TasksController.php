<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2026 Max Pozdeev <maxpozdeev@gmail.com>
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
    function get($username = null)
    {
        if (!$this->resolveTargetUser($username)) {
            return;
        }

        $listId = (int)_get('list');
        checkReadAccess($listId);
        $db = DBConnection::instance();

        if ($listId === -1) {
            $listRepo = new ListRepo($db);
            $publicOnly = !$this->req->isViewerOwnerOfTarget();
            $lists = array_keys($listRepo->findListNamesByUserId($this->req->ownerUserId(), $publicOnly));
        }
        else {
            $lists = [ $listId ];
        }

        # dont show completed tasks by default
        $showCompleted = false;
        if (_get('compl') == 1) {
            # show both
            $showCompleted = null;
        }

        $tags = [
            'excludeAll' => false,
            'includeAny' => false,
            'include' => [],
            'exclude' => [],
            'search' => [],  # plain array of tag ids
        ];
        $t = trim(_get('t'));
        if ($t != '') {
            $at = explode(',', $t);
            $tagNames = [];
            $exTagNames = [];
            foreach ($at as $atv) {
                $atv = trim($atv);
                if ($atv == '')
                    continue;
                // tasks without tags (ignore other tags included or excluded)
                if ($atv == '^') {
                    $tags = [ 'excludeAll' => true ];
                    $tagNames = $exTagNames = [];
                    break;
                }
                // tasks with any tag
                else if ($atv == '^^') {
                    $tags['includeAny'] = true;
                }
                else if (substr($atv,0,1) == '^') {
                    $exTagNames[] = substr($atv,1);
                } else {
                    $tagNames[] = $atv;
                }
            }
            $tagRepo = new TagRepo($db);
            # TODO: maybe use tag ids?
            if (count($tagNames) > 0) {
                # 2-dimensional array
                foreach ($tagNames as $tagName) {
                    $tags['include'][] = $tagRepo->getTagIdsByName($tagName);
                }

            }
            if (count($exTagNames) > 0) {
                # 1-dimensional array
                foreach ($exTagNames as $tagName) {
                    array_push($tags['exclude'], ...$tagRepo->getTagIdsByName($tagName));
                }
            }
        }

        $due = trim(_get('due'));
        # dont show completed tasks if duedate filter is active
        if ($due === 'past' || $due === 'today' || $due === 'soon') $showCompleted = false;

        $search = trim(_get('s'));
        $sort = (int)_get('sort');
        $searchOptions = (int)_get('so');
        $searchTags = (($searchOptions & TaskRepo::SEARCH_TAGS) === TaskRepo::SEARCH_TAGS);
        if ($searchTags) {
            $tags['search'] = (new TagRepo($db))->getTagIdsBySearchName($search);
        }

        $page = (int)_get('page');
        $limit = (int)_get('limit');
        $paginator = null;
        if ($limit > 0 && $page > 0) {
            $paginator = TaskPaginator::paginatorWithPageAndLimit($page, $limit);
        }

        $t = array();
        $t['total'] = 0;
        $t['time'] = time();
        $t['list'] = [];

        $taskRepo = new TaskRepo($db);
        $tasks = $taskRepo->findTasks($lists, $showCompleted, $tags, $search, $sort, $due, $searchOptions, 0, 0, $paginator);
        foreach ($tasks as $task) {
            $t['list'][] = $task->toJsonApiArray();
        }
        $t['total'] = count($t['list']);
        if ($paginator) {
            $t['pagination_total'] = $paginator->total;
            $t['pagination_page'] = $paginator->page;
            $t['pagination_limit'] = $paginator->limit;
        }
        else if ($limit == -1) {
            $t['pagination_total'] = $t['total'];
            $t['pagination_page'] = 1;
            $t['pagination_limit'] = -1;
        }

        if ( (_get('saveCompl') || _get('saveSort'))  && haveWriteAccess($listId))
        {
            if (!isset($listRepo))
                $listRepo = new ListRepo($db);

            $list = $listRepo->getListById($listId, userId());

            if (_get('saveCompl'))
                $list->setIsShowCompleted( $showCompleted === null );
            if (_get('saveSort'))
                $list->setSort($sort);

            if ($list instanceof AlltasksList)
                $listRepo->updateAlltasksList($list);
            else
                $listRepo->updateListProperties($list);
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
        $listId = (int)($this->req->jsonBody['list'] ?? 0);
        if ($listId <= 0) {
            $this->response->errorJsonContent(__("listNotFound"), 404);
            return;
        }
        $list = checkAndGetListForWrite($listId);
        if ($action == 'newFull') {
            $this->response->data = $this->fullNewTaskInList($list);
        }
        else {
            $this->response->data = $this->newTaskInList($list);
        }
    }


    /**
     * Delete multiple tasks
     * @return void
     */
    function delete(): void
    {
        checkWriteAccess();
        $tasks = $this->getRequestedTasks();
        if ($tasks === null) {
            return;
        }

        # delete one by one to send notification for each task
        $deleted = [];
        foreach ($tasks as $task) {
            if ($this->deleteTask($task)['total']) {
                $deleted[] = [ 'id' => $task->id ];
            }
        }
        $this->response->data = [
            'ok' => true,
            'total' => count($deleted),
            'list' => $deleted,
        ];
    }


    /**
     * Actions with multiple tasks
     * @return void
     */
    function patch(): void
    {
        checkWriteAccess();
        $tasks = $this->getRequestedTasks();
        if ($tasks === null) {
            return;
        }
        $body = $this->req->jsonBody;

        # We determine which action to take based on the property specified in the request.
        if (isset($body['compl'])) {
            $this->response->data = $this->completeTasks($tasks);
        }
        else if (isset($body['listId'])) {
            $list = (new ListRepo(DBConnection::instance()))->findRealListById((int)$body['listId']);
            if (!$list) {
                $this->response->errorJsonContent(__("listNotFound"), 404);
                return;
            }
            checkWriteAccessToList($list);
            $this->response->data = $this->moveTasks($tasks, $list);
        }
    }


    private function completeTasks(array $tasks): ?array
    {
        $ids = [];
        foreach ($tasks as $task) {
            if ($this->completeTask($task)['total']) {
                $ids[] = [ 'id' => $task->id ];
            }
        }
        return [
            'ok' => true,
            'total' => count($ids),
            'list' => $ids,
        ];
    }


    /**
     *
     * @param Task[] $tasks
     * @param TaskList $list
     * @return null|array
     */
    private function moveTasks(array $tasks, TaskList $list): ?array
    {
        $ids = [];
        $repo = new TaskRepo(DBConnection::instance());
        foreach ($tasks as $task) {
            if ($task->listId === $list->id) continue;
            if ($task->setList($list)) {
                $repo->updateTaskProperties($task);
                $ids[] = [ 'id' => $task->id ];
                MTTNotificationCenter::postNotification(MTTNotification::didEditTask, [
                    'property' => 'listId',
                    'task' => $task
                ]);
            }
        }
        if (count($ids) === 0)  {
            return  [
                'ok' => false,
                'total' => 0,
                'error' => "Failed to move tasks",
            ];
        }
        return [
            'ok' => true,
            'total' =>count($ids),
            'list' => $ids,
        ];
    }


    function getId($id): void
    {
        $id = (int)$id;
        $repo = new TaskRepo(DBConnection::instance());
        $task = $repo->findTaskById($id);
        if (!$task) {
            $this->response->errorJsonContent(__("taskNotFound"), 404);
            return;
        }
        checkReadAccess($task->listId);
        $t = [
            'ok' => true,
            'total' => 1,
            'list' => [
                $task->toJsonApiArray()
            ],
        ];
        $this->response->data = $t;
    }

    /**
     * Delete task by Id
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    function deleteId($id): void
    {
        checkWriteAccess();
        $id = (int)$id;
        $repo = new TaskRepo(DBConnection::instance());
        $task = $repo->findTaskById($id);
        if (!$task) {
            $this->response->errorJsonContent(__("taskNotFound"), 404);
            return;
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
    function putId($id): void
    {
        $id = (int)$id;
        $repo = new TaskRepo(DBConnection::instance());
        $task = $repo->findTaskById($id);
        if (!$task) {
            $this->response->errorJsonContent(__("taskNotFound"), 404);
            return;
        }
        $list = checkAndGetListForWrite($task->listId);

        $action = $this->req->jsonBody['action'] ?? '';
        switch ($action) {
            case 'edit':     $this->response->data = $this->editTask($task, $list); break;
            case 'complete': $this->response->data = $this->completeTask($task); break;
            case 'note':     $this->response->data = $this->editNote($task);     break;
            case 'move':     $this->response->data = $this->moveTask($task);     break;
            case 'priority': $this->response->data = $this->priorityTask($task); break;
            case 'delete':   $this->response->data = $this->deleteTask($task);   break; //compatibility
            default:         $this->response->errorJsonContent("Unexpected action", 400); return;
        }
    }


    /**
     * Change position of a task within its list.
     * Body must contain exactly one of `afterId` or `beforeId` - id of the neighbour task.
     * Task is placed immediately after (or before) the anchor task.
     * @param mixed $id
     * @return void
     * @throws Exception
     */
    function putPosition($id): void
    {
        $id = (int)$id;
        $repo = new TaskRepo(DBConnection::instance());
        $task = $repo->findTaskById($id);
        if (!$task) {
            $this->response->errorJsonContent(__("taskNotFound"), 404);
            return;
        }
        checkWriteAccess($task->listId);

        $body = $this->req->jsonBody;
        $hasAfter = array_key_exists('afterId', $body);
        $hasBefore = array_key_exists('beforeId', $body);
        if ($hasAfter === $hasBefore) {
            $this->response->errorJsonContent("Exactly one of `afterId` or `beforeId` must be set", 400);
            return;
        }
        $isAfter = $hasAfter;
        $anchorId = (int)($isAfter ? $body['afterId'] : $body['beforeId']);
        if ($anchorId <= 0) {
            $this->response->errorJsonContent("Unexpected task id in `". ($isAfter ? 'afterId' : 'beforeId'). "`", 400);
            return;
        }
        if ($anchorId === $task->id) {
            # already in place
            $this->response->data = [
                'ok' => true,
                'total' => 1,
                'list' => [ [
                    'id' => $task->id,
                    'ow' => $task->ow,
                ] ],
            ];
            return;
        }

        $result = $repo->changeTaskPosition($task, $anchorId, $isAfter);
        if ($result === null) {
            $this->response->errorJsonContent(__("taskNotFound"), 404);
            return;
        }
        if ($result === false) {
            $this->response->errorJsonContent("Anchor task is in another list or has another completion state", 409);
            return;
        }

        MTTNotificationCenter::postNotification(MTTNotification::didEditTask, [
            'property' => 'ow',
            'task' => $task
        ]);

        $this->response->data = [
            'ok' => true,
            'total' => 1,
            'list' => [ [
                'id' => $task->id,
                'ow' => $task->ow,
            ] ],
        ];
    }


    /**
     * Parse task input string to components for representing in edit/add form
     * @return void
     * @throws Exception
     */
    function postParseTitle(): void
    {
        checkWriteAccess();
        $t = array(
            'title' => trim( $this->req->jsonBody['title'] ?? '' ),
            'prio' => 0,
            'tags' => '',
            'duedate' => '',
        );
        if (Config::get('smartsyntax') != 0 && (null !== $a = parseSmartSyntax($t['title'])))
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


    function postNewTaskCounter(): void
    {
        $curList = (int) ($this->req->jsonBody['list'] ?? 0);
        $curLater = (int) ($this->req->jsonBody['later'] ?? 0);

        checkWriteAccess(); # need to be logged
        checkReadAccess($curList);

        /** @var array{listId:int|string,later:int|null}[] */
        $lists = $this->req->jsonBody['lists'] ?? [];

        if (!is_array($lists)) {
            $this->response->errorJsonContent("Invalid argument", 400);
            return;
        }

        # remove lists without access granted
        /** @var array<int,int> */
        $listsLater = [];
        if ($lists)
        {
            foreach ($lists as $item) {
                $id = (int)($item['listId'] ?? 0);
                $later = (int) ($item['later'] ?? 0);
                $listsLater[$id] = $later;
            }
            $listRepo = new ListRepo(DBConnection::instance());
            $filteredLists = $listRepo->filterReadableListsForUser(userId(), array_keys($listsLater));

            $listsLater = array_filter($listsLater, function($listId) use ($filteredLists) {
                return in_array( $listId, $filteredLists );
            }, ARRAY_FILTER_USE_KEY);
        }

        $a = [];
        if ($listsLater) {
            $taskRepo = new TaskRepo(DBConnection::instance());
            $a = $taskRepo->counterOfNewTasksInLists($listsLater);
        }

        $time = time();

        $b = [];
        if ($curLater > 0) {
            $taskRepo = new TaskRepo(DBConnection::instance());
            $b = $taskRepo->idsOfNewTasksInList($curList, $curLater);
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

    private function newTaskInList(TaskList $list): ?array
    {
        $failedResult = [
            'ok' => false,
            'total' => 0,
            'error' => "Invalid argument"
        ];
        $title = trim($this->req->jsonBody['title'] ?? '');
        $prio = 0;
        $tags = '';
        $duedate = null;
        if (Config::get('smartsyntax') != 0)
        {
            $a = parseSmartSyntax($title);
            if ($a === null) {
                return $failedResult;
            }
            $title = (string)$a['title'];
            $prio = (int)$a['prio'];
            $tags = (string)$a['tags'];
            if (isset($a['duedate']) && preg_match("|^\d+-\d+-\d+$|", $a['duedate'])) {
                $duedate = $a['duedate'];
            }
        }
        if ($title == '') {
            return $failedResult;
        }
        if (Config::get('autotag')) {
            $tags .= ',' . ($this->req->jsonBody['tag'] ?? '');
        }

        $task = Task::create($title, $list->id);
        $task->setPriority($prio);
        $task->setDuedate($duedate);
        $task->tagNames = explode(',', $tags);

        $repo = new TaskRepo(DBConnection::instance());
        $repo->saveTask($task, $list);
        MTTNotificationCenter::postNotification(MTTNotification::didCreateTask, $task);

        return [
            'ok' => true,
            'total' => 1,
            'list' => [ $task->toJsonApiArray() ],
        ];
    }

    private function fullNewTaskInList(TaskList $list): ?array
    {
        $title = trim($this->req->jsonBody['title'] ?? '');
        if ($title == '')
            return [
                'ok' => false,
                'total' => 0,
                'error' => "Invalid argument"
            ];
        $note = $this->req->jsonBody['note'] ?? '';
        $prio = (int)($this->req->jsonBody['prio'] ?? 0);
        if ($prio < -1)
            $prio = -1;
        elseif ($prio > 2)
            $prio = 2;
        $duedate = MTTSmartSyntax::parseDuedate(trim( $this->req->jsonBody['duedate'] ?? '' ));
        $tags = $this->req->jsonBody['tags'] ?? '';
        if (Config::get('autotag'))
            $tags .= ',' . ($this->req->jsonBody['tag'] ?? '');

        $task = Task::create($title, $list->id);
        $task->setNote($note);
        $task->setPriority($prio);
        $task->setDuedate($duedate);
        $task->tagNames = explode(',', $tags);

        $repo = new TaskRepo(DBConnection::instance());
        $repo->saveTask($task, $list);
        MTTNotificationCenter::postNotification(MTTNotification::didCreateTask, $task);

        return [
            'ok' => true,
            'total' => 1,
            'list' => [ $task->toJsonApiArray() ],
        ];
    }

    private function editTask(Task $task, TaskList $list): ?array
    {
        $title = trim($this->req->jsonBody['title'] ?? '');
        if ($title == '')
            return [
                'ok' => false,
                'total' => 0,
                'error' => "Invalid argument"
            ];
        $note = $this->req->jsonBody['note'] ?? '';
        $prio = (int)($this->req->jsonBody['prio'] ?? 0);
        if ($prio < -1)
            $prio = -1;
        elseif ($prio > 2)
            $prio = 2;
        $duedate = MTTSmartSyntax::parseDuedate(trim( $this->req->jsonBody['duedate'] ?? '' ));
        $tags = trim( $this->req->jsonBody['tags'] ?? '' );

        $task->setTitle($title);
        $task->setNote($note);
        $task->setPriority($prio);
        $task->setDuedate($duedate);
        $task->tagNames = explode(',', $tags);

        $repo = new TaskRepo(DBConnection::instance());
        $repo->saveTask($task, $list);

        MTTNotificationCenter::postNotification(MTTNotification::didEditTask, [
            'task' => $task
        ]);

        return [
            'ok' => true,
            'total' => 1,
            'list' => [ $task->toJsonApiArray() ],
        ];
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

        if ($toId <= 0 || $task->listId == $toId) {
            return $failedResult;
        }
        $list = checkAndGetListForWrite($toId);

        if ($task->setList($list)) {
            $repo = new TaskRepo(DBConnection::instance());
            $repo->updateTaskProperties($task);
            MTTNotificationCenter::postNotification(MTTNotification::didEditTask, [
                'property' => 'listId',
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



    /**
     * Reads ids from request body, loads tasks and checks write access to their lists.
     * Sets error response and returns null on failure.
     * @return Task[]|null
     */
    private function getRequestedTasks(): ?array
    {
        $ids = $this->req->jsonBody['ids'] ?? '';
        if (!is_array($ids) || !array_is_list($ids) || empty($ids)) {
            $this->response->errorJsonContent("Malformed field: `ids`", 400);
            return null;
        }

        /** @var Task[] $tasks */
        $tasks = [];
        $listIds = [];
        $repo = new TaskRepo(DBConnection::instance());

        foreach ($ids as $id) {
            $task = $repo->findTaskById((int)$id);
            if (!$task) continue;
            $listIds[] = $task->listId;
            $tasks[] = $task;
        }
        if (empty($tasks)) {
            $this->response->errorJsonContent(__("taskNotFound"), 404);
            return null;
        }
        foreach (array_unique($listIds) as $listId) {
            checkWriteAccess($listId);
        }

        return $tasks;
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

}
