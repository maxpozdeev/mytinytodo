<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class TagsController extends ApiController {

    /**
     * Get tag cloud
     * @return array
     * @throws Exception
     */
    function getCloud($listId)
    {
        $listId = (int)$listId;
        checkReadAccess($listId);
        $db = DBConnection::instance();

        $q = $db->dq("SELECT name,tag_id,COUNT(tag_id) AS tags_count FROM {$db->prefix}tag2task INNER JOIN {$db->prefix}tags ON tag_id=id ".
                            "WHERE list_id=$listId GROUP BY (tag_id) ORDER BY tags_count ASC");
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
        $count = sizeof($at);
        if (!$count) {
            return $t;
        }

        $qmax = max($ac);
        $qmin = min($ac);
        if ($count >= 10) $grades = 10;
        else $grades = $count;
        $step = ($qmax - $qmin)/$grades;
        foreach ($at as $i => $tag)
        {
            $t['cloud'][] = array(
                'tag' => htmlspecialchars($tag['name']),
                'id' => (int)$tag['id'],
                'w' => $this->tagWeight($qmin, $ac[$i], $step)
            );
        }
        $t['total'] = $count;
        return $t;
    }

    function getSuggestions($listId)
    {
        $listId = (int)_get('list');
        checkWriteAccess($listId);
        $db = DBConnection::instance();
        $begin = trim(_get('q'));
        $limit = 8;
        $q = $db->dq("SELECT name,id FROM {$db->prefix}tags INNER JOIN {$db->prefix}tag2task ON id=tag_id WHERE list_id=$listId AND name LIKE ".
                        $db->quoteForLike('%s%%',$begin) ." GROUP BY tag_id ORDER BY name LIMIT $limit");
        $t = array();
        while ($r = $q->fetchRow()) {
            $t[] = $r[0];
        }
        return $t;
    }

    private function tagWeight(int $qmin, int $q, float $step)
    {
        if ($step == 0) return 1;
        $v = ceil(($q - $qmin)/$step);
        if ($v == 0) return 0;
        else return $v-1;
    }


}
