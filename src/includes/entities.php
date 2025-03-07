<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

abstract class AbstractEntity
{
    protected static array $dbfields;

    public static function checkDbFields(array $a)
    {
        // Check all fields are present and no more
        foreach (static::$dbfields as $field) {
            if (!array_key_exists($field, $a))
                throw new Exception("Field `$field' does not present in input");
        }
        if (count(static::$dbfields) != count($a))
            throw new Exception("Unexpected number of fields in input");
    }

    abstract public static function fromArray(array $a): self;
    abstract function toArray(bool $onlyChanged = false): array;
}

interface JsonApiSerialization
{
    function toJsonArray(): array;
    function toPublicJsonArray(): array;
}

abstract class AbstractTaskList extends AbstractEntity implements JsonApiSerialization
{
    public ?int $id;
    public ?int $userId;
    abstract function setSort(int $sort);
    abstract function setIsHidden(bool $hidden);
}

class TaskList extends AbstractTaskList
{
    protected static array $dbfields = ['id','user_id','uuid','ow','name','d_created','d_edited','sorting','published','taskview','extra'];
    protected array $changed = [];

    public ?int $id;
    public ?int $userId;                    # user_id
    public ?string $uuid;
    #public ?mixed $ow;
    public ?string $name;
    public ?int $d_created;
    public ?int $d_edited;
    public ?int $sorting;
    public bool $isPublished = false;       # published
    public bool $isShowCompleted = false;   # taskview
    public bool $isShowNotes = false;       # taskview
    public bool $isHidden = false;          # taskview
    public ?array $extra = null;

    function setName(string $name)
    {
        if ($name === '')
            throw new InvalidArgumentException("List name is empty");
        $name = str_replace(['"',"'",'<','>','&'], '', $name);
        $this->name = $name;
        $this->d_edited = time();
        $this->changed['name'] = true;
        $this->changed['d_edited'] = true;
    }

    function setSort(int $sort)
    {
        if ($sort < 0 || ($sort > 5 && $sort < 100) || $sort > 105)
            $sort = 0;
        $this->sorting = $sort;
        $this->d_edited = time();
        $this->changed['sorting'] = true;
        $this->changed['d_edited'] = true;
    }

    function setIsPublished(bool $published)
    {
        $this->isPublished = $published;
        $this->d_edited = time();
        $this->changed['published'] = true;
        $this->changed['d_edited'] = true;
    }

    function setIsShowNotes(bool $show)
    {
        $this->isShowNotes = $show;
        $this->changed['taskview'] = true;
    }

    function setIsHidden(bool $hidden)
    {
        $this->isHidden = $hidden;
        $this->changed['taskview'] = true;
    }

    function setFeedKey(string $feedKey)
    {
        if ($feedKey === '' && $this->extra)
            unset($this->extra['feedKey']);
        else
            $this->extra['feedKey'] = $feedKey;
        $this->d_edited = time();
        $this->changed['extra'] = true;
        $this->changed['d_edited'] = true;
    }

    function toArray(bool $onlyChanged = false): array
    {
        $a = [
            'id' => $this->id,
            'user_id' => $this->userId,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'd_created' => $this->d_created,
            'd_edited' => $this->d_edited,
            'sorting' => $this->sorting,
            'published' => $this->isPublished ? 1 : 0,
            'taskview' => ($this->isShowCompleted ? 1:0) + ($this->isShowNotes ? 2:0) + ($this->isHidden ? 4:0),
            'extra' => null,
        ];
        if ($this->extra) {
            $a['extra'] = json_encode($this->extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (!$onlyChanged)
            return $a;

        $b = [];
        $fields = array_keys($this->changed);
        foreach ($fields as $field) {
            $b[$field] = $a[$field];
        }
        return $b;
    }

    static function fromArray(array $a) : self
    {
        $entity = new static();
        $entity::checkDbFields($a);
        $entity->id = (int)$a['id'];
        $entity->userId = (int)$a['user_id'];
        $entity->uuid = $a['uuid'];
        #$entity->ow = $a['ow'];
        $entity->name = $a['name'];
        $entity->d_created = (int)$a['d_created'];
        $entity->d_edited = (int)$a['d_edited'];
        $entity->sorting = (int)$a['sorting'];
        $entity->isPublished = boolval($a['published']);

        $flags = (int)$a['taskview'];
        $entity->isShowCompleted = boolval($flags & 1);
        $entity->isShowNotes = boolval($flags & 2);
        $entity->isHidden = boolval($flags & 4);

        if (isset($a['extra'])) {
            $extra = json_decode($a['extra'], true, 10, JSON_INVALID_UTF8_SUBSTITUTE);
            if ($extra === false) {
                error_log("Failed to decode JSON data of list extra with id={$entity->id}: " . json_last_error_msg());
                $extra = [];
            }
            # save all keys (even not used)
            $entity->extra = $extra;
        }

        return $entity;
    }

    function toJsonArray(bool $public = false): array
    {
        $feedKey = '';
        if (!$public) {
            $feedKey = (string) ($this->extra['feedKey'] ?? '');
        }

        return array(
            'id' => $this->id ?? '',
            'name' => htmlspecialchars($this->name ?? ''),
            'sort' => $this->sorting ?? 0,
            'published' => $this->isPublished ? 1 : 0,
            'showCompl' => $this->isShowCompleted ? 1 : 0,
            'showNotes' => $this->isShowNotes ? 1 : 0,
            'hidden' => $this->isHidden ? 1 : 0,
            'feedKey' => $feedKey,
        );
    }

    function toPublicJsonArray(): array
    {
        return $this->isPublished ? $this->toJsonArray(true) : [];
    }
}


class AlltasksList extends AbstractTaskList
{
    protected static array $dbfields = [];

    public ?int $id = -1;
    public ?int $userId;
    public int $sorting = 0;
    public bool $isShowCompleted = false;
    public bool $isHidden = false;

    function setSort(int $sort)
    {
        if ($sort < 0 || ($sort > 5 && $sort < 100) || $sort > 105)
            $sort = 0;
        $this->sorting = $sort;
    }

    function setIsHidden(bool $hidden)
    {
        $this->isHidden = $hidden;
    }

    static function fromArray(array $opts): self
    {
        $entity = new static();
        if (isset($opts['sort']))
            $entity->sorting = (int)$opts['sort'];
        if (isset($opts['hidden']))
            $entity->isHidden = boolval($opts['hidden']);
        if (isset($opts['showCompleted']))
            $entity->isShowCompleted = boolval($opts['showCompleted']);
        return $entity;
    }

    function toArray(bool $onlyChanged = false): array
    {
        $a = [];
        $default = new static();
        if ($this->sorting != $default->sorting)
            $a['sort'] = $this->sorting;
        if ($this->isShowCompleted != $default->isShowCompleted)
            $a['showCompleted'] = $this->isShowCompleted;
        if ($this->isHidden != $default->isHidden)
            $a['hidden'] = $this->isHidden;
        return $a;
    }

    function toJsonArray(): array
    {
        return array(
            'id' => -1,
            'name' => htmlspecialchars(__('alltasks')),
            'sort' => $this->sorting,
            'published' => 0,
            'showCompl' => $this->isShowCompleted ? 1 : 0,
            'showNotes' => 0,
            'hidden' => $this->isHidden ? 1 : 0,
            'feedKey' => '',
        );
    }

    function toPublicJsonArray(): array
    {
        return [];
    }
}
