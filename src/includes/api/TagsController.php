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
        if (!$this->resolveTargetUser($username, true)) {
            return;
        }
        $listId = (int)$listId;
        $db = DBConnection::instance();

        if ($listId === -1) {
            checkReadAccess(-1);
            $publishedOnly = !$this->req->isOwnerOfTarget();
            $aTags = (new TagRepo($db))->getCloud(-1, $this->req->ownerUserId(), $publishedOnly);
        }
        else {
            $list = checkAndGetListForRead($listId);
            $aTags = (new TagRepo($db))->getCloud((int)$list->id, $this->req->ownerUserId());
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
    function getSuggestions(): void
    {
        $listId = (int)_get('list');
        checkWriteAccess($listId);
        $db = DBConnection::instance();
        $prefix = trim(_get('q'));
        $this->response->data = (new TagRepo($db))->getSuggestions($listId, $prefix, 8);
    }

}
