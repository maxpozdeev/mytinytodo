<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class TagsController extends ApiController {

    /**
     * Get tag cloud
     * @return void
     * @throws Exception
     */
    function getCloud($listId): void
    {
        $listId = (int)$listId;
        checkReadAccess($listId);
        $db = DBConnection::instance();
        $aTags = (new TagRepo($db))->getCloud($listId, (int)$this->req->userId());

        $t = array();
        $t['total'] = 0;
        $count = count($aTags);
        if (!$count) {
            $this->response->data = $t;
            return;
        }

        foreach ($aTags as $tag)
        {
            $t['items'][] = array(
                'tag' => htmlspecialchars($tag['name']),
                'tagText' => (string)$tag['name'],
                'id' => (int)$tag['id'],
            );
        }
        $t['total'] = $count;
        $this->response->data = $t;
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
