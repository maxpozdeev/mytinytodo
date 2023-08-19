<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class MTTNotificationCenter
{
    /**
     * @var array<string, MTTNotificationObserverInterface[]|callable[]>
     */
    private static $observers = [];

    /**
     * @param string $notification
     * @param MTTNotificationObserverInterface $observer
     * @return void
     */
    public static function addObserverForNotification(string $notification, MTTNotificationObserverInterface $observer)
    {
        if (!isset(self::$observers[$notification])) {
            self::$observers[$notification] = [];
        }
        if (!in_array($observer, self::$observers[$notification])) {
            // do not duplicate same observer
            self::$observers[$notification][] = $observer;
        }
    }

    /**
     * @param string[] $notifications
     * @param MTTNotificationObserverInterface $observer
     * @return void
     */
    public static function addObserverForNotifications(array $notifications, MTTNotificationObserverInterface $observer)
    {
        foreach ($notifications as $notification) {
            self::addObserverForNotification($notification, $observer);
        }
    }

    /**
     *
     * @param string $notification
     * @param callable $callback
     * @return void
     */
    public static function addCallbackForNotification(string $notification, callable $callback)
    {
        if (!isset(self::$observers[$notification])) {
            self::$observers[$notification] = [];
        }
        self::$observers[$notification][] = $callback;
    }

    /**
     *
     * @param string $notification
     * @return bool
     */
    public static function hasObserversForNotification(string $notification): bool
    {
        if (isset(self::$observers[$notification]) && count(self::$observers[$notification]) > 0) {
            return true;
        }
        return false;
    }

    public static function postNotification(string $notification, $object)
    {
        if (!isset(self::$observers[$notification])) {
            return; // No observers for this notification
        }
        foreach (self::$observers[$notification] as $observer) {
            if ($observer instanceof MTTNotificationObserverInterface) {
                $observer->notification($notification, $object);
            }
            else {
                $observer($object);
            }
        }
    }

    /**
     * Run this near exit()
     * @return void
     */
    public static function postDidFinishRequestNotification()
    {
        if ( ! isset(self::$observers[MTTNotification::didFinishRequest]) ) {
            return; // No observers for didFinishRequest
        }
        if (function_exists('fastcgi_finish_request')) {
            if (session_status() == PHP_SESSION_ACTIVE) {
                session_write_close(); // Close active session
            }
            fastcgi_finish_request();
        }
        self::postNotification(MTTNotification::didFinishRequest, null);
    }
}

interface MTTNotificationObserverInterface
{
    function notification(string $notification, $object);
}

// Enum
abstract class MTTNotification
{
    const didFinishRequest = 'didFinishRequest';
    const didCreateTask = 'didCreateTask';
    const didEditTask = 'didEditTask';
    const didDeleteTask = 'didDeleteTask';
    const didCompleteTask = 'didCompleteTask';
    const didCreateList = 'didCreateList';
    const didDeleteList = 'didDeleteList';
    const didDeleteCompletedInList = 'didDeleteCompletedInList';
}

function add_action(string $notification, callable $callback)
{
    MTTNotificationCenter::addCallbackForNotification($notification, $callback);
}

function do_action(string $notification, $object = null)
{
    MTTNotificationCenter::postNotification($notification, $object);
}
