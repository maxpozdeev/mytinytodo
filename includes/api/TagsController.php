<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
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

        $sqlWhere = ($listId == -1) ? "" : "WHERE list_id = $listId";
        $q = $db->dq("SELECT name, tag_id, COUNT(tag_id) AS tags_count
                      FROM {$db->prefix}tag2task INNER JOIN {$db->prefix}tags ON tag_id = id
                      $sqlWhere
                      GROUP BY tag_id, name
                      ORDER BY tags_count DESC");
        $at = array();
        $ac = array();
        while ($r = $q->fetchAssoc()) {
            $at[] = array(
                'name' => $r['name'],
                'id' => $r['tag_id']
            );
            $ac[] = (int) $r['tags_count'];
        }

        $t = array();
        $t['total'] = 0;
        $count = count($at);
        if (!$count) {
            $this->response->data = $t;
            return;
        }

        $qmax = max($ac);
        $qmin = min($ac);
        if ($count >= 10) $grades = 10;
        else $grades = $count;
        $step = ($qmax - $qmin)/$grades;
        foreach ($at as $i => $tag)
        {
            $t['items'][] = array(
                'tag' => htmlspecialchars($tag['name']),
                'id' => (int)$tag['id'],
                'count' => $ac[$i],
                'w' => $this->tagWeight($qmin, $ac[$i], $step)
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
        $q = $db->dq("SELECT name, tag_id AS id FROM {$db->prefix}tags
                      INNER JOIN {$db->prefix}tag2task ON id=tag_id
                      WHERE list_id=$listId AND ". $db->like('name', '%s%%', $begin). "
                      GROUP BY tag_id, name
                      ORDER BY name
                      LIMIT $limit");
        $t = array();
        while ($r = $q->fetchRow()) {
            $t[] = $r[0];
        }
        $this->response->data = $t;
    }

    private function tagWeight(int $qmin, int $q, float $step): float
    {
        if ($step == 0) return 1.0;
        $v = ceil(($q - $qmin)/$step);
        if ($v == 0) return 0.0;
        else return $v - 1.0;
    }


}
