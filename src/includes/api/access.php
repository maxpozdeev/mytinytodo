<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

/**
 * Standard access denied response (HTTP 403).
 */
function denyAccess(): void
{
    (new JsonApiResponse([
        'ok' => false,
        'total' => 0,
        'list' => [],
        'denied' => 1,
        'error' => __('denied'),
    ], 403))->exit();
}


/**
 * Check read access to a real list or to the virtual all tasks list (-1).
 */
function checkReadAccess(int $listId): void
{
    if ($listId === -1) {
        $req = ApiRequest::instance();
        $ownerId = $req->ownerUserId();
        $viewerId = userId() ?? 0;
        if ($ownerId === 0 || ($ownerId === $viewerId && !is_logged()))
            denyAccess();
    }
    else {
        checkAndGetListForRead($listId);
    }
}

/**
 * Ensure that requested list is readable by the current user.
 * Returns the list instance.
 * @throws Exception
 */
function checkAndGetListForRead(int $listId): TaskList
{
    $req = ApiRequest::instance();
    $repo = new ListRepo(DBConnection::instance());
    $ownerId = $req->isTargetExplicit() ? $req->targetUserId() : null;
    $list = $repo->findRealListById($listId, $ownerId);
    if (!$list) {
        if (is_logged())
            ErrorApiResponse::exitWithMessage(__("listNotFound"), 404);
        denyAccess();
    }
    if (!canReadList($list))
        denyAccess();
    return $list;
}


function checkWriteAccess(?int $listId = null)
{
    if (haveWriteAccess($listId))
        return;
    denyAccess();
}

function checkWriteAccessToList(AbstractTaskList $list)
{
    if (canWriteToList($list) && ApiRequest::instance()->isOwnerOfTarget())
        return;
    denyAccess();
}

/**
 * @param int $listId
 * @param bool $onlyReal
 * @return AlltasksList|TaskList
 * @throws Exception
 */
function checkAndGetListForWrite(int $listId, bool $onlyReal = false): AbstractTaskList
{
    $viewerId = userId();
    if (!is_logged() || !$viewerId || !ApiRequest::instance()->isOwnerOfTarget())
        denyAccess();

    if ($onlyReal && $listId === -1)
        ErrorApiResponse::exitWithMessage(__("listNotFound"), 404);

    $repo = new ListRepo(DBConnection::instance());
    $list = ($listId === -1) ? $repo->alltasksListByUserId($viewerId) : $repo->findRealListById($listId);
    if (!$list)
        ErrorApiResponse::exitWithMessage(__("listNotFound"), 404);
    if (!canWriteToList($list))
        denyAccess();

    return $list;
}

function haveWriteAccess(?int $listId = null) : bool
{
    if (!is_logged())
        return false;

    # currently a logged user have write access to own lists only
    $req = ApiRequest::instance();
    if (!$req->isOwnerOfTarget())
        return false;
    $reqUserId = $req->ownerUserId();

    // check list exist
    if ($listId !== null && $listId != -1)
    {
        $db = DBConnection::instance();
        $count = $db->sq("SELECT COUNT(*) FROM {$db->prefix}lists WHERE id=? AND user_id=?",
            array($listId, $reqUserId));
        if (!$count)
            return false;
    }
    return true;
}
