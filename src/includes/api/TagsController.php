<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class TagsController extends ApiController {

    /**
     * Get tag cloud of a user: all tasks (listId = -1) or a single list.
     * @param mixed $username Owner of the requested lists
     * @param mixed $listId List id or -1 for all tasks
     * @return void
     * @throws Exception
     */
    function getUserCloud($username, $listId): void
    {
        $username = trim((string)$username);
        $listId = (int)$listId;

        if ($username === '') {
            $this->response->errorJsonContent("User not found", 404);
            return;
        }
        $db = DBConnection::instance();

        if ($username === username() && userId()) {
            # request for own lists
            $targetUserId = (int)userId();
        }
        else {
            # request for lists of another user
            $targetUserId = (int)(new UserRepo($db))->findUserIdByUsername($username);
            if (!$targetUserId) {
                $this->response->errorJsonContent("User not found", 404);
                return;
            }
        }
        $this->req->setUserId($targetUserId, $username);

        if ($listId === -1) {
            $publishedOnly = ($targetUserId !== (userId() ?? 0));
            if (!$publishedOnly) {
                // own alltasks include unpublished lists and require a logged-in user
                checkReadAccess(-1);
            }
            $aTags = (new TagRepo($db))->getCloud(-1, $targetUserId, $publishedOnly);
        }
        else {
            $list = (new ListRepo($db))->findRealListById($listId, $targetUserId);
            if (!$list) {
                if (is_logged())
                    ErrorApiResponse::exitWithMessage(__("listNotFound"), 404);
                else
                    ErrorApiResponse::exitWithMessage(__("denied"), 403);
            }
            if (!canReadList($list))
                ErrorApiResponse::exitWithMessage(__("denied"), 403);
            $aTags = (new TagRepo($db))->getCloud($listId, $targetUserId);
        }

        $this->response->data = $this->cloudToArray($aTags);
    }

    /**
     * @param array<int, array{name: string, id: int}> $aTags
     * @return array{total: int, items?: array<int, array{tag: string, tagText: string, id: int}>}
     */
    private function cloudToArray(array $aTags): array
    {
        $t = ['total' => 0];
        $count = count($aTags);
        if (!$count)
            return $t;

        foreach ($aTags as $tag) {
            $t['items'][] = [
                'tag' => htmlspecialchars($tag['name']),
                'tagText' => (string)$tag['name'],
                'id' => (int)$tag['id'],
            ];
        }
        $t['total'] = $count;
        return $t;
    }

    /**
     * Suggest tags by its prefix (for jQuery UI autocomplete widget)
     * @return void
     * @throws Exception
     */
    function getSuggestions($listId): void
    {
        $listId = (int)_get('list');
        checkWriteAccess($listId);
        $db = DBConnection::instance();
        $prefix = trim(_get('q'));
        $this->response->data = (new TagRepo($db))->getSuggestions($listId, $prefix, 8);
    }

}
