<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class MTTFilterCenter
{
    private static $filters = [];

    public static function addFilterCallbackForAction(string $action, callable $callback): bool
    {
        if (!isset(self::$filters[$action])) {
            self::$filters[$action] = [];
        }
        if (!in_array($callback, self::$filters[$action])) {
            // do not duplicate same callback
            self::$filters[$action][] = $callback;
            return true;
        }
        return false;
    }


    public static function addFilterForAction(string $action, MTTFilterInterface $filter): bool
    {
        if (!isset(self::$filters[$action])) {
            self::$filters[$action] = [];
        }
        if (!in_array($filter, self::$filters[$action])) {
            // do not duplicate same filter
            self::$filters[$action][] = $filter;
            return true;
        }
        return false;
    }


    public static function hasFiltersForAction(string $action): bool
    {
        if (isset(self::$filters[$action]) && count(self::$filters[$action]) > 0) {
            return true;
        }
        return false;
    }


    public static function filter(string $action, $in, &$out): bool
    {
        if (!isset(self::$filters[$action]) || count(self::$filters[$action]) == 0) {
            return false;
        }
        foreach (self::$filters[$action] as $filter) {
            if ($filter instanceof MTTFilterInterface) {
                $filter->filter($in, $out);
            }
            else {
                $filter($in, $out);
            }
        }
        return true;
    }
}

interface MTTFilterInterface
{
    function filter($in, &$out);
}

function add_filter(string $action, MTTFilterInterface $filter) {
    MTTFilterCenter::addFilterForAction($action, $filter);
}

function add_filter_callback(string $action, callable $callback) {
    MTTFilterCenter::addFilterCallbackForAction($action, $callback);
}

function do_filter(string $action, $in, &$out): bool {
    return MTTFilterCenter::filter($action, $in, $out);
}


