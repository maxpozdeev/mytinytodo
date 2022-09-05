<?php declare(strict_types=1);

class MTTNotificationCenter
{
    /**
     * @var array<string, MTTNotificationObserverInterface[]>
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


    public static function postNotification(string $notification, $object)
    {
        if (!isset(self::$observers[$notification])) {
            return; // No observers for this notification
        }
        foreach (self::$observers[$notification] as $observer) {
            $observer->notification($notification, $object);
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
    const didCreateList = 'didCreateList';
}

