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
    function getCloud($listId)
    {
        $listId = (int)$listId;
        checkReadAccess($listId);
        $db = DBConnection::instance();

        $sqlWhere = 'WHERE tags.user_id='. (int)$this->req->userId();
        if ($listId != -1)
            $sqlWhere .= " AND t2t.list_id = $listId";

        $collate = ($db::DBTYPE === DBConnection::DBTYPE_SQLITE) ? "COLLATE UTF8CI" : "";

        $q = $db->dq("SELECT DISTINCT tag_id, name
                      FROM {$db->prefix}tag2task AS t2t INNER JOIN {$db->prefix}tags AS tags ON tag_id = id
                      $sqlWhere
                      ORDER BY name $collate ASC");
        $aTags = array();
        while ($r = $q->fetchAssoc()) {
            $aTags[] = array(
                'name' => $r['name'],
                'id' => $r['tag_id']
            );
        }

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
     * @return void
     * @throws Exception
     */
    function getSuggestions($listId)
    {
        $listId = (int)_get('list');
        checkWriteAccess($listId);
        $db = DBConnection::instance();
        $begin = trim(_get('q'));
        $limit = 8;
        $collate = ($db::DBTYPE === DBConnection::DBTYPE_SQLITE) ? "COLLATE UTF8CI" : "";
        $q = $db->dq("SELECT name, tag_id AS id FROM {$db->prefix}tags
                      INNER JOIN {$db->prefix}tag2task ON id=tag_id
                      WHERE list_id=$listId AND ". $db->like('name', '%s%%', $begin). "
                      GROUP BY tag_id, name
                      ORDER BY name $collate
                      LIMIT $limit");
        $t = array();
        while ($r = $q->fetchRow()) {
            $t[] = $r[0];
        }
        $this->response->data = $t;
    }

}
