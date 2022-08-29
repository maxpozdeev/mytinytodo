<?php declare(strict_types=1);

class MTTNotificationCenter
{
    /**
     * @var array<string, MTTNotificationObserverInterface[]>
     */
    private static $observers = [];

    /**
     * @var array<array>
     */
    private static $notificationStore = [];

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
        self::$observers[$notification][] = $observer;
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


    public static function postNotification(string $notification, $object)
    {
        self::$notificationStore[] = [
            'notification' => $notification,
            'object' => $object
        ];
    }

    /**
     * Run this near exit()
     * @return void
     */
    public static function notifyDelayedObservers()
    {
        if (count(self::$notificationStore) == 0) {
            return; // No notifications
        }
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_write_close(); // Close active session
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        foreach (self::$notificationStore as $notificationItem) {
            $notification = $notificationItem['notification'];
            $object = $notificationItem['object'];
            $observers = self::$observers[$notification] ?? [];
            foreach ($observers as $observer) {
                $observer->notification($notification, $object);
            }
        }
    }
}

interface MTTNotificationObserverInterface
{
    function notification(string $notification, $object);
}

// Enum
abstract class MTTNotification
{
    const didCreateTask = 'didCreateTask';
    const didCreateList = 'didCreateList';
}

