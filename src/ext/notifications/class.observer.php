<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace Notify;

use NotificationsExtension;
use MTTNotification;
use MTTNotificationCenter;
use DBConnection;


class NotificationObserver implements \MTTNotificationObserverInterface
{
    private $prefs = null;
    private $delayedNotifications = [];

    public function notification(string $notification, $object)
    {
        if (!$this->prefs) {
            $this->init();
        }
        if (count($this->prefs['chats']) == 0 && count($this->prefs['emails']) == 0) {
            return; // nobody to notify
        }

        $db = DBConnection::instance();
        switch ($notification) {
            case MTTNotification::didFinishRequest:
                $this->processDelayed();
                break;
            case MTTNotification::didCreateTask:
            case MTTNotification::didCreateList:
                // Get list name
                $list = $db->sqa( "SELECT name FROM {$db->getPrefix()}lists WHERE id=?", array($object['listId'] ?? 0) );
                $object['listName'] = htmlspecialchars($list['name'] ?? '');
                $this->delayedNotifications[] = [
                    'notification' => $notification,
                    'object' => $object
                ];
                MTTNotificationCenter::addObserverForNotification(MTTNotification::didFinishRequest, $this);
        }
    }

    private function processDelayed()
    {
        //$db = DBConnection::instance();
        $useCli = !function_exists('fastcgi_finish_request');
        $sender = new Sender( $this->prefs, $useCli );
        foreach ($this->delayedNotifications as $item) {
            $sender->notify($item);
        }
    }

    private function init()
    {
        $this->prefs = NotificationsExtension::preferences();
        //$this->token = $this->prefs['token'] ?? '';
    }

}
