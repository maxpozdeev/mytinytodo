<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2011,2020-2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

$dontStartSession = 1;
require_once('./init.php');

$listId = (int)_get('list');

$listRepo = new ListRepo(DBConnection::instance());
$list = $listRepo->findRealListById($listId);

if (!$list) {
    if (is_logged())
        die("No list found.");
    else
        die("Access denied.");
}

if (!canReadList($list, trim(_get('key')))) {
    die("Access denied.");
}

$feedType = _get('feed');
$taskRepo = new TaskRepo(DBConnection::instance());

$feed = new MTTRSSFeed($list, $taskRepo);
$feed->feedType($feedType);
$feed->print();


class MTTRSSFeed
{
    protected TaskRepo $taskRepo;
    protected TaskList $list;
    protected array $feedData = [];
    /** @var object{task:Task,field:string}[] */
    protected array $data = [];

    function __construct(TaskList $list, TaskRepo $repo)
    {
        $this->list = $list;
        $this->taskRepo = $repo;
    }

    function feedType(string $feedType)
    {
        $lang = Lang::instance();
        if ($feedType == 'completed') {
            $this->feedData['_feed_descr'] = $lang->get('feed_completed_tasks');
            $this->addData('d_completed', TaskRepo::FILTER_COMPLETED);
        }
        elseif ($feedType == 'modified') {
            $this->feedData['_feed_descr'] = $lang->get('feed_modified_tasks');
            $this->addData('d_edited', 0);
        }
        elseif ($feedType == 'current') {
            $this->feedData['_feed_descr'] = $lang->get('feed_new_tasks');
            $this->addData('d_created', TaskRepo::FILTER_OPEN);
        }
        elseif ($feedType == 'status') {
            $this->feedData['_feed_descr'] = $lang->get('feed_tasks');
            $this->addData('d_created', 0 );
            $this->addData('d_edited', TaskRepo::FILTER_OPEN_AND_EDITED);
            $this->addData('d_completed', TaskRepo::FILTER_COMPLETED);
        }
        else {
            $this->feedData['_feed_descr'] = $lang->get('feed_new_tasks');
            $feedType = 'tasks';
            $this->addData('d_created', 0);
        }

        $this->feedData['_feed_title'] = sprintf($lang->get('feed_title'), $this->list->name) . ' - '. $this->feedData['_feed_descr'];
        $this->feedData['_feed_link'] = get_unsafe_mttinfo('mtt_url'). "feed.php?list={$this->list->id}&feed=$feedType";
        $this->feedData['_feed_type'] = $feedType;
        htmlarray_ref($this->feedData);
    }

    function addData(string $sortField, int $filter)
    {
        if     ($sortField == 'd_created')   $sort = TaskRepo::SORT_FIELD_CREATED;
        elseif ($sortField == 'd_edited')    $sort = TaskRepo::SORT_FIELD_EDITED;
        elseif ($sortField == 'd_completed') $sort = TaskRepo::SORT_FIELD_COMPLETED;
        else throw new Exception("Unexpected sort field: '$sortField'");

        $tasks = $this->taskRepo->findTasks([$this->list->id], null, [], '', $sort, $filter, 100);
        foreach ($tasks as $task)
        {
            $this->data[] = (object)array(
                'task' => $task,
                'field' => $sortField,
            );
        }
    }


    function print()
    {
        $lang = Lang::instance();
        $link = get_mttinfo('url'). "?list={$this->list->id}"; #escaped
        $buildDate = htmlspecialchars(gmdate('r'));

        $s = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
            "<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n".
            "<channel>\n".
            "<title>{$this->feedData['_feed_title']}</title>\n".
            "<link>$link</link>\n".
            "<atom:link href=\"{$this->feedData['_feed_link']}\" rel=\"self\" type=\"application/rss+xml\"/>\n".
            "<description>{$this->feedData['_feed_descr']}</description>\n".
            "<lastBuildDate>$buildDate</lastBuildDate>\n\n";

        foreach ($this->data as $v)
        {
            $task = $v->task;
            $guid = htmlspecialchars($this->feedData['_feed_type']. '-'. $this->list->id. '-'. $v->task->id. '-'. $v->task->{$v->field});
            $itemLink = $link. "&amp;task=". $v->task->id; #escaped

            $status = '';
            if ( $this->feedData['_feed_type'] == 'status' ) {
                if ( $v->field == 'd_created' ) {
                    $status = $lang->get('feed_status_new');
                }
                elseif ( $v->field == 'd_edited' ) {
                    $status = $lang->get('feed_status_updated');
                }
                elseif ( $v->field == 'd_completed' ) {
                    $status = $lang->get('feed_status_completed');
                }
                if ($status != '')
                    $status = "[$status] ";
            }
            $pubDate = htmlspecialchars(gmdate('r', $v->task->{$v->field}));

            $a = array();
            $a[] = $lang->get('task'). ": ". $task->title;
            if ($task->priority) {
                $a[] = $lang->get('priority'). ": ". ($task->priority > 0 ? '+' : ''). $task->priority;
            }
            if ($task->duedate != '') {
                $ad = explode('-', $task->duedate);
                $a[] = $lang->get('due'). ": ".formatDate3(Config::get('dateformat'), (int)$ad[0], (int)$ad[1], (int)$ad[2], $lang);
            }
            if ($task->tagNames) {
                $a[] = $lang->get('tags'). ": ". implode(", ", $task->tagNames);
            }
            if ($task->isCompleted) {
                $a[] = $lang->get('taskdate_completed'). ": ". timestampToDatetime($task->d_completed);
            }
            $descr = implode("<br/>", htmlarray($a));
            if ($task->note != '')
                $descr .= "<br/><br/>". $task->noteHtml(true);

            $s .= "\t<item>\n".
                "\t\t<title>". htmlspecialchars($status. $v->task->title). "</title>\n".
                "\t\t<link>". $itemLink. "</link>\n".
                "\t\t<pubDate>". $pubDate. "</pubDate>\n".
                "\t\t<description><![CDATA[". $descr. "]]></description>\n".
                "\t\t<guid isPermaLink=\"false\">$guid</guid>\n".
                "\t</item>\n\n";
        }

        $s .= "</channel>\n</rss>";

        header("Content-type: text/xml; charset=utf-8");
        print $s;
    }
}
