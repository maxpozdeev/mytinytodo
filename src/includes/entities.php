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
    //abstract public static function toArray(self $entity): array;
}

interface JsonApiSerialization
{
    function toJsonArray(): array;
    function toPublicJsonArray(): array;
}

abstract class AbstractTaskList extends AbstractEntity implements JsonApiSerialization
{
}

class TaskList extends AbstractTaskList
{
    protected static array $dbfields = ['id','user_id','uuid','ow','name','d_created','d_edited','sorting','published','taskview','extra'];

    public ?int $id;
    public ?int $userId;
    public ?string $uuid;
    #public ?mixed $ow;
    public ?string $name;
    public ?int $d_created;
    public ?int  $d_edited;
    public ?int $sorting;
    public bool $isPublished = false;
    public bool $isShowCompleted = false;
    public bool $isShowNotes = false;
    public bool $isHidden = false;
    public ?array $extra = null;

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
                error_log("Failed to decodes JSON data of list extra Id={$entity->id}: " . json_last_error_msg());
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

    public int $sorting = 0;
    public bool $isShowCompleted = false;
    public bool $isHidden = false;

    static function fromArray(array $opts): self
    {
        $entity = new static();
        $entity->sorting = (int)$opts['sort'];
        $entity->isHidden = (int)$opts['hidden'] ? true : false;
        $entity->isShowCompleted = (int)$opts['showCompleted'] ? true : false;
        return $entity;
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
