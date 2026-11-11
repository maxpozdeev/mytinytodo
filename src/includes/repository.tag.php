<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class TagRepo
{
    protected AbstractDatabase $db;

    function __construct(AbstractDatabase $db)
    {
        $this->db = $db;
    }


    /**
     *
     * @param string[] $tagNames
     * @param int $userId
     * @return Tag[]
     * @throws Exception
     */
    public function getTags(array $tagNames, int $userId): array
    {
        $tags = array();
        foreach ($tagNames as $tagName)
        {
            $tagName = str_replace(array('^','#'), '', trim($tagName));
            if ($tagName == '')
                continue;

            $newtag = $this->getOrCreateTag($tagName, $userId);
            # no duplicates by id
            $tags[$newtag->id] = $newtag;
        }
        return array_values($tags);
    }


    /**
     * Finds tag by its name. Return array of id.
     * @param string $name
     * @return int[]
     */
    public function getTagIdsByName(string $name): array
    {
        $ids = [];
        $q = $this->db->dq("SELECT id FROM {$this->db->prefix}tags WHERE name=?", [$name]);
        while ($r = $q->fetchAssoc()) {
            $ids[] = (int) $r['id'];
        }
        return $ids;
    }


    public function getTagIdsBySearchName(string $name): array
    {
        $ids = [];
        $q = $this->db->dq("SELECT id FROM {$this->db->prefix}tags WHERE ". $this->db->aiciLike("name", "%%%s%%", $name));
        while ($r = $q->fetchAssoc()) {
            $ids[] = (int) $r['id'];
        }
        return $ids;
    }


    /**
     *
     * @param string $name
     * @param int $userId
     * @return Tag
     * @throws Exception
     */
    public function getOrCreateTag(string $name, int $userId): Tag
    {
        $tag = new Tag();
        $tag->name = $name;
        $tag->userId = $userId;

        $tag->id = (int) $this->db->sq("SELECT id FROM {$this->db->prefix}tags WHERE user_id=? AND name=?", [$userId, $name]);
        if (!$tag->id) {
            $this->db->ex("INSERT INTO {$this->db->prefix}tags (user_id,name) VALUES (?,?)", [$userId, $name]);
            $tag->id = (int) $this->db->lastInsertId();
        }
        if (!$tag->id)
            throw new Exception("Failed to get id for tag: ". $name);

        return $tag;
    }

    /**
     * Get tag cloud for a real list or virtual alltasks list.
     * @return array<int, array{name: string, id: int}>
     */
    public function getCloud(int $listId, int $userId): array
    {
        if ($listId === -1)
            $sqlWhere = "WHERE tags.user_id = $userId";
        else
            $sqlWhere = "WHERE t2t.list_id = $listId";

        $orderBy = $this->tagNameOrderByCollation();
        $q = $this->db->dq(
            "SELECT DISTINCT tag_id, name
             FROM {$this->db->prefix}tag2task AS t2t INNER JOIN {$this->db->prefix}tags AS tags ON tag_id = id
             $sqlWhere
             ORDER BY name $orderBy ASC");
        $aTags = [];
        while ($r = $q->fetchAssoc()) {
            $aTags[] = [
                'name' => $r['name'],
                'id' => (int) $r['tag_id'],
            ];
        }
        return $aTags;
    }

    /**
     * Get tag name suggestions for a list.
     * @return string[]
     */
    public function getSuggestions(int $listId, string $begin, int $limit = 8): array
    {
        $orderBy = $this->tagNameOrderByCollation();
        $where = $this->db->aiciLike('name', '%s%%', $begin);
        $q = $this->db->dq(
            "SELECT name, tag_id AS id FROM {$this->db->prefix}tags
             INNER JOIN {$this->db->prefix}tag2task ON id=tag_id
             WHERE list_id=$listId AND $where
             GROUP BY tag_id, name
             ORDER BY name $orderBy
             LIMIT $limit");
        $t = [];
        while ($r = $q->fetchRow()) {
            $t[] = $r[0];
        }
        return $t;
    }

    private function tagNameOrderByCollation(): string
    {
        if ($this->db::DBTYPE === DBConnection::DBTYPE_SQLITE)
            return "COLLATE {$this->db->orderCollation}";
        if ($this->db::DBTYPE === DBConnection::DBTYPE_MYSQL)
            return "COLLATE {$this->db->orderCollation}";
        return '';
    }

}
