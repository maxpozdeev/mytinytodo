<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025 Max Pozdeev <maxpozdeev@gmail.com>
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
     * Finds all variations of tag by its "normalized" name. Return array of id.
     * @param string[] $tagNames
     * @return int[]
     */
    public function getTagIdsByName(string $name): array
    {
        $ids = [];
        $q = $this->db->dq("SELECT id FROM {$this->db->prefix}tags WHERE ". $this->db->ciEquals('name', $name));
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
}
