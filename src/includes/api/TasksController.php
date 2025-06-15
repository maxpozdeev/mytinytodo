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

        if ($listId == -1) {
            $listRepo = new ListRepo($db);
            $userLists = $listRepo->findListNamesByUserId( userId() );
            $lists = array_keys($userLists);
        }
        else {
            $lists = [ $listId ];
        }

        $isCompleted = null;
        if (_get('compl') == 0) {
            $isCompleted = false;
        }

        $tags = [
            'excludeAll' => false,
            'includeAny' => false,
            'include' => [],
            'exclude' => [],
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

        $search = trim(_get('s'));
        $sort = (int)_get('sort');

        $t = array();
        $t['total'] = 0;
        $t['time'] = time();
        $t['list'] = [];

        $taskRepo = new TaskRepo($db);
        $tasks = $taskRepo->findTasks($lists, $isCompleted, $tags, $search, $sort);
        foreach ($tasks as $task) {
            if ($listId == -1) {
                //$r['list_name'] = $userLists[ (string)$r['list_id'] ] ?? '((undefined))';

            }

            $t['list'][] = $task->toJsonApiArray();
        }
        $t['total'] = count($t['list']);

        // TODO: use repo instead of controller
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
            default:      return $this->response->errorJsonContent("Unexpected action", 400);
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
            case 'edit':     $this->response->data = $this->editTask($task);     break;
            case 'complete': $this->response->data = $this->completeTask($task); break;
            case 'note':     $this->response->data = $this->editNote($task);     break;
            case 'move':     $this->response->data = $this->moveTask($task);     break;
            case 'priority': $this->response->data = $this->priorityTask($task); break;
            case 'delete':   $this->response->data = $this->deleteTask($task);   break; //compatibility
            default:         return $this->response->errorJsonContent("Unexpected action", 400);
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


    function postCounterOfNewTasks()
    {
        $curList = (int) ($this->req->jsonBody['list'] ?? 0);
        $curLater = (int) ($this->req->jsonBody['later'] ?? 0);

        haveWriteAccess(); # need to be logged
        checkReadAccess($curList);

        /** @var array{listId:int|string,later:int|null}[] */
        $lists = $this->req->jsonBody['lists'] ?? [];

        if (!is_array($lists)) {
            return [
                'ok' => false,
                'error' => "Invalid argument"
            ];
        }

        # remove lists without access granted
        if ($lists)
        {
            /** @var array<int,int> */
            $listsLater = [];
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

    private function newTaskInList(int $listId): ?array
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
            if ($a === false) {
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

        $task = Task::create($title, $listId);
        $task->setPriority($prio);
        $task->setDuedate($duedate);
        $task->tagNames = explode(',', $tags);

        $repo = new TaskRepo(DBConnection::instance());
        $repo->saveTask($task, $this->req->userId());
        MTTNotificationCenter::postNotification(MTTNotification::didCreateTask, $task);

        return [
            'ok' => true,
            'total' => 1,
            'list' => [ $task->toJsonApiArray() ],
        ];
    }

    private function fullNewTaskInList(int $listId): ?array
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

        $task = Task::create($title, $listId);
        $task->setNote($note);
        $task->setPriority($prio);
        $task->setDuedate($duedate);
        $task->tagNames = explode(',', $tags);

        $repo = new TaskRepo(DBConnection::instance());
        $repo->saveTask($task, $this->req->userId());
        MTTNotificationCenter::postNotification(MTTNotification::didCreateTask, $task);

        return [
            'ok' => true,
            'total' => 1,
            'list' => [ $task->toJsonApiArray() ],
        ];
    }

    private function editTask(Task $task): ?array
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
        $repo->saveTask($task, $this->req->userId());

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

}
