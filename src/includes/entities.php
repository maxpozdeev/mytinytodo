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
        // Check all fields are present
        foreach (static::$dbfields as $field) {
            if (!array_key_exists($field, $a))
                throw new Exception("Field `$field' does not present in input");
        }
        //  and no more
        if (count(static::$dbfields) != count($a)) {
            if (MTT_DEBUG) {
                $diff = array_diff(array_keys($a), static::$dbfields);
                throw new Exception("Unexpected fields in input: ". implode(', ', $diff));
            }
            throw new Exception("Unexpected number of fields in input");
        }
    }

    abstract public static function fromArray(array $a): self;
    abstract function toArray(bool $onlyChanged = false): array;
}

interface JsonApiSerialization
{
    function toJsonApiArray(): array;
    function toPublicJsonApiArray(): array;
}

abstract class AbstractTaskList extends AbstractEntity implements JsonApiSerialization
{
    public ?int $id;
    public ?int $userId;
    abstract function setSort(int $sort);
    abstract function setIsHidden(bool $hidden);
    const defaultSort = 3;
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
    public int $sorting = parent::defaultSort;
    public bool $isPublished = false;       # published
    public bool $isShowCompleted = false;   # taskview & 1
    public bool $isShowNotes = false;       # taskview & 2
    public bool $isHidden = false;          # taskview & 4
    public ?array $extra = null;

    function setName(string $name)
    {
        if ($name === '')
            throw new InvalidArgumentException("List name is empty");

        $this->name = $name;
        $this->d_edited = time();
        $this->changed['name'] = true;
        $this->changed['d_edited'] = true;
    }

    function setSort(int $sort)
    {
        if ($sort < 0 || ($sort > 5 && $sort < 100) || $sort > 105)
            $sort = self::defaultSort;
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

    function setIsShowCompleted(bool $showCompleted)
    {
        $this->isShowCompleted = $showCompleted;
        $this->changed['taskview'] = true;
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

    function toJsonApiArray(bool $public = false): array
    {
        $feedKey = '';
        if (!$public) {
            $feedKey = (string) ($this->extra['feedKey'] ?? '');
        }

        return array(
            'id' => $this->id ?? '',
            'name' => htmlspecialchars($this->name ?? ''),
            'sort' => $this->sorting ?? self::defaultSort,
            'published' => $this->isPublished ? 1 : 0,
            'showCompl' => $this->isShowCompleted ? 1 : 0,
            'showNotes' => $this->isShowNotes ? 1 : 0,
            'hidden' => $this->isHidden ? 1 : 0,
            'feedKey' => $feedKey,
        );
    }

    function toPublicJsonApiArray(): array
    {
        return $this->isPublished ? $this->toJsonApiArray(true) : [];
    }
}


class AlltasksList extends AbstractTaskList
{
    protected static array $dbfields = [];

    public ?int $id = -1;
    public ?int $userId;
    public int $sorting = parent::defaultSort;
    public bool $isShowCompleted = false;
    public bool $isHidden = false;

    function setSort(int $sort)
    {
        if ($sort < 0 || ($sort > 5 && $sort < 100) || $sort > 105)
            $sort = self::defaultSort;
        $this->sorting = $sort;
    }

    function setIsHidden(bool $hidden)
    {
        $this->isHidden = $hidden;
    }

    function setIsShowCompleted(bool $showCompleted)
    {
        $this->isShowCompleted = $showCompleted;
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

    function toJsonApiArray(): array
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

    function toPublicJsonApiArray(): array
    {
        return [];
    }
}



abstract class AbstractTask extends AbstractEntity
{
    public ?int $id;
    public ?int $listId;
}

class Task extends AbstractTask
{
    protected static array $dbfields = ['id','uuid','list_id','parent_id','d_created','d_completed','d_edited',
        'compl','title','note','prio','duedate','extra','ow','tags_ids','tags','list_name'];
    protected array $changed = [];

    public ?int $id = null;
    public ?string $uuid;
    public ?int $listId;                # list_id
    public ?int $parentId = null;       # parent_id
    public ?string $title;              # title
    public ?string $note = null;        # note
    public int $d_created = 0;
    public int $d_edited = 0;
    public int $d_completed = 0;
    public bool $isCompleted = false;   # compl
    public int $priority = 0;           # prio
    public ?string $duedate = null;
    public ?array $extra = null;

    public int $ow = 0;
    protected ?array $tagIds;
    public ?array $tagNames;

    protected ?string $listName;

    static function fromArray(array $a) : self
    {
        $entity = new static();
        $entity::checkDbFields($a);
        $entity->id = (int)$a['id'];
        $entity->uuid = (string)$a['uuid'];
        $entity->listId = (int)$a['list_id'];
        $entity->parentId = (int)$a['parent_id'];
        $entity->title = (string)$a['title'];
        $entity->note = (string)$a['note'];
        $entity->d_created = (int)$a['d_created'];
        $entity->d_edited = (int)$a['d_edited'];
        $entity->d_completed = (int)$a['d_completed'];
        $entity->isCompleted = boolval($a['compl']);
        $entity->priority = (int)$a['prio'];
        $entity->duedate = (string)$a['duedate'];
        $entity->listName = (string)$a['list_name'];
        $entity->ow = (int)$a['ow'];
        $entity->tagNames = ($a['tags'] != '') ? explode(',', $a['tags']) : []; // TODO: use '#' to separate tags
        $entity->tagIds = ($a['tags_ids'] != '') ? explode(',', $a['tags_ids']) : [];

        if (isset($a['extra'])) {
            $extra = json_decode($a['extra'], true, 10, JSON_INVALID_UTF8_SUBSTITUTE);
            if ($extra === false) {
                error_log("Failed to decode JSON data of task extra with id={$entity->id}: " . json_last_error_msg());
                $extra = [];
            }
            # save all keys (even not used)
            $entity->extra = $extra;
        }

        return $entity;
    }


    function changedFields(): array
    {
        return array_keys($this->changed);
    }

    function toArray(bool $onlyChanged = false): array
    {
        $a = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'list_id' => $this->listId,
            'title' => $this->title,
            'note' => $this->note,
            'compl' => $this->isCompleted ? 1 : 0,
            'prio' => $this->priority,
            'd_created' => $this->d_created,
            'd_edited' => $this->d_edited,
            'd_completed' => $this->d_completed,
            'extra' => null,
            'ow' => $this->ow,
            //FIXME: tags!
            'tags_ids' => $this->tags_ids ?? '',
            'tags' => $this->tags ?? '',
        ];
        if ($this->extra) {
            $a['extra'] = json_encode($this->extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (!$onlyChanged)
            return $a;

        $b = [];
        $fields = $this->changedFields();
        foreach ($fields as $field) {
            $b[$field] = $a[$field];
        }
        return $b;
    }

    function toJsonApiArray(): array
    {
        $lang = Lang::instance();

        $isEdited = ($this->d_edited && $this->d_edited != $this->d_created);
        $dCreated = timestampToDatetime($this->d_created);
        $dEdited = $isEdited ? timestampToDatetime($this->d_edited) : '';
        $dCompleted = $this->d_completed ? timestampToDatetime($this->d_completed) : '';

        if (!Config::get('showtime')) {
            $dCreatedFull = timestampToDatetime($this->d_created, true);
            $dEditedFull = $isEdited ? timestampToDatetime($this->d_edited, true) : '';
            $dCompletedFull = $this->d_completed ? timestampToDatetime($this->d_completed, true) : '';
        }
        else {
            $dCreatedFull = $dCreated;
            $dEditedFull = $dEdited;
            $dCompletedFull = $dCompleted;
        }
        $dueA = static::prepareDuedate($this->duedate);

        return array(
            'id' => $this->id ?? '',                //int
            'listId' => $this->listId ?? 0,         //int
            'listName' => htmlspecialchars($this->listName ?? ''),
            'title' => $this->titleHtml(),
            'titleText' => $this->title,            // raw, not escaped
            'note' => $this->noteHtml(),
            'noteText' => $this->note ?? '',        // raw, not escaped
            'compl' => $this->isCompleted ? 1 : 0,
            'prio' => $this->priority,              //int
            'isEdited' => (bool)$isEdited,          //bool

            'date' => htmlspecialchars($dCreated),
            'dateInt' => $this->d_created,                      //int
            'dateFull' => htmlspecialchars($dCreatedFull),
            'dateInlineTitle' => htmlspecialchars(sprintf($lang->get('taskdate_inline_created'), $dCreated)), //TODO: move preparing of *inlineTitle to js

            'dateEdited' => htmlspecialchars($dEdited),
            'dateEditedInt' => $this->d_edited,                 //int
            'dateEditedFull' => htmlspecialchars($dEditedFull),
            'dateEditedInlineTitle' => htmlspecialchars(sprintf($lang->get('taskdate_inline_edited'), $dEdited)), //todo:!

            'dateCompleted' => htmlspecialchars($dCompleted),
            'dateCompletedFull' => htmlspecialchars($dCompletedFull),
            'dateCompletedInlineTitle' => $dCompleted !== '' ? htmlspecialchars(sprintf($lang->get('taskdate_inline_completed'), $dCompleted)) : '',

            'duedate' => htmlspecialchars($dueA['formatted']),
            'dueClass' => htmlspecialchars($dueA['class']),
            'dueStr' => htmlspecialchars($dueA['str']),
            'dueInt' => $dueA['int'],                           //int
            'dueTitle' => $dueA['formattedlong'] !== '' ? htmlspecialchars(sprintf($lang->get('taskdate_inline_duedate'), $dueA['formattedlong'])) : '',

            'tags' => htmlspecialchars( implode(',', $this->tagNames) ),
            'tags_ids' => htmlspecialchars( implode(',', $this->tagIds) ),

            //FIXME: dont use ow
            'ow' => $this->ow ?? 0,
        );
    }


    static function create(string $title, int $listId): Task
    {
        if ($title === '' || !$listId) {
            throw new InvalidArgumentException("Unspecified title or list id");
        }
        $task = new static();
        $task->title = $title;
        $task->listId = $listId;
        $task->uuid = generateUUID();
        $task->d_created = time();
        $task->d_edited = $task->d_created;
        return $task;
    }

    /**
     * Get formatted title
     * @return string
     */
    function titleHtml(): string
    {
        return titleMarkup($this->title ?? '');
    }

    /**
     * Get formatted note using markup method defined in settings
     * @return string
     */
    function noteHtml(bool $toExternal = false): string
    {
        return noteMarkup($this->note, $toExternal);
    }


    function setTitle(string $title): bool
    {
        if ($title === '') {
            throw new InvalidArgumentException("Unspecified title");
        }
        $this->title = $title;
        $this->changed['title'] = true;

        if ($this->id) {
            $this->d_edited = time();
            $this->changed['d_edited'] = true;
        }
        return true;
    }

    /**
     * Change the completed state of a task
     * Return true of the state was changed
     * @param bool $completed
     * @return bool
     */
    function setIsCompleted(bool $completed): bool
    {
        if ($completed === $this->isCompleted)
            return false;
        $this->isCompleted = $completed;
        $this->d_completed = $completed ? time() : 0;
        # NB: we do not change the edited date (new in v2.0)

        $this->changed['compl'] = true;
        $this->changed['d_completed'] = true;
        return true;
    }

    function setNote(string $note): bool
    {
        $note = str_replace("\r\n", "\n", $note);
        if ($this->note === $note)
            return false;

        $this->note = $note;
        $this->changed['note'] = true;

        if ($this->id) {
            $this->d_edited = time();
            $this->changed['d_edited'] = true;
        }
        return true;
    }

    function setPriority(int $prio): bool
    {
        if ($this->priority === $prio)
            return false;

        if ($prio < -1 || $prio > 2)
            throw new InvalidArgumentException("Unexpected priority value: $prio");

        $this->priority = $prio;
        $this->changed['prio'] = true;

        if ($this->id) {
            $this->d_edited = time();
            $this->changed['d_edited'] = true;
        }
        return true;
    }

    function setDuedate(?string $duedate): bool
    {
        if ($this->duedate === $duedate)
            return false;

        $this->duedate = $duedate;
        $this->changed['duedate'] = true;

        if ($this->id) {
            $this->d_edited = time();
            $this->changed['d_edited'] = true;
        }
        return true;
    }


    function setList(TaskList $list): bool
    {
        if ($this->listId === $list->id) {
            $this->listName = $list->name;
            return false;
        }

        $this->listId = $list->id;
        # NB: we do not change the edited date (new in v2.0)
        $this->listName = $list->name;

        $this->changed['list_id'] = true;
        return true;
    }

    /**
     *
     * @param Tag[] $tags
     * @return void
     */
    function setTags(array $tags)
    {
        $this->tagIds = [];
        $this->tagNames = [];
        foreach ($tags as $tag) {
            $this->tagIds[] = $tag->id;
            $this->tagNames[] = $tag->name;
        }
    }

    /**
     * Parse duedate and prepare array of properties for Json Api
     * @param null|string $duedate
     * @return array{class:string,str:string,formatted:string,formattedlong:string,timestamp:int,int:int}
     */
    static function prepareDuedate(?string $duedate): array
    {
        $lang = Lang::instance();

        $a = array( 'class'=>'', 'str'=>'', 'formatted'=>'', 'formattedlong'=>'', 'timestamp'=>0, 'int'=>0 );
        if (is_null($duedate) || $duedate === '') {
            return $a;
        }
        $ad = explode('-', $duedate);
        $y = (int)$ad[0];
        $m = (int)$ad[1];
        $d = (int)$ad[2];
        $a['timestamp'] = mktime(0, 0, 0, $m, $d, $y);
        $a['int'] = $y * 10000 + $m * 100 + $d;

        $oToday = new DateTimeImmutable(date("Y-m-d"));
        $oDue = new DateTimeImmutable($duedate);
        $oDiff = $oToday->diff($oDue);
        if ($oDiff === false) {
            return $a;
        }
        $thisYear = ((int)$oToday->format('Y') == $y);
        $days = $oDiff->days;
        if ($oDiff->invert) $days *= -1;

        $exact = Config::get('exactduedate') ? true : false;

        if ($days < -7 && !$thisYear) {
            $a['class'] = 'past';
            $a['str'] = formatDate3(Config::get('dateformat2'), $y, $m, $d, $lang);
        }
        elseif ($days < -7) {
            $a['class'] = 'past';
            $a['str'] = formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($days < -1) {
             $a['class'] = 'past';
             $a['str'] = !$exact ? sprintf($lang->get('daysago'), abs($days)) : formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($days == -1)  {
             $a['class'] = 'past';
             $a['str'] = !$exact ? $lang->get('yesterday') : formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($days == 0) {
            $a['class'] = 'today';
            $a['str'] = !$exact ? $lang->get('today') : formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($days == 1) {
            $a['class'] = 'today';
            $a['str'] = !$exact ? $lang->get('tomorrow') : formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($days <= 7) {
            $a['class'] = 'soon';
            $a['str'] = !$exact ? sprintf($lang->get('indays'), $days) : formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        elseif ($thisYear) {
            $a['class'] = 'future';
            $a['str'] = formatDate3(Config::get('dateformatshort'), $y, $m, $d, $lang);
        }
        else {
            $a['class'] = 'future';
            $a['str'] = formatDate3(Config::get('dateformat2'), $y, $m, $d, $lang);
        }

        #avoid short year
        $fmt = str_replace('y', 'Y', Config::get('dateformat2'));
        $a['formatted'] = formatTime($fmt, $a['timestamp']);
        $a['formattedlong'] = formatTime(Config::get('dateformat'), $a['timestamp']);

        return $a;
    }

}


class Tag
{
    public int $id;
    public string $name;
    public int $userId;
}
