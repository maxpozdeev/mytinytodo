<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace Notify;

use NotificationsExtension;
use Config;

class Controller extends \ApiController
{
    function postDeactivateAll()
    {
        $prefs = Config::requestDomain(NotificationsExtension::domain);
        if (isset($prefs['chats'])) {
            $prefs['chats'] = [];
            Config::saveDomain(NotificationsExtension::domain, $prefs);
        }
        $this->response->data = [ 'total' => 1, 'msg' => __("notifications.all_chats_deactivated") ];
    }

    function postCheck()
    {
        $prefs = Config::requestDomain(NotificationsExtension::domain);
        if (!($prefs['validToken'] ?? false)) {
            $this->response->data = [ 'total' => 0, 'msg' => __("notifications.bot_not_configured") ];
            return;
        }
        if (!isset($prefs['chats']) || !is_array($prefs['chats'])) {
            $prefs['chats'] = [];
        }
        $code = $prefs['code'] ?? null;
        $codeExpires = $prefs['codeExpires'] ?? 0;
        $token = $prefs['token'] ?? '';

        $this->response->data = [ 'total' => 0, 'msg' => __("notifications.no_new_chats") ];

        // Read messages since last check
        $maxId = $prefs['lastUpdateId'] ?? 0;
        $api = new TelegramApi($token);
        $updates = $api->getUpdates([
            'offset' => $maxId + 1,
            'allowed_updates' => ['message']
        ]);
        if (!is_array($updates) || count($updates) == 0) {
            return;
        }

        // Select last message in every chat
        $messages = array();
        foreach ($updates as $update) {
            $message = $update['message'] ?? [];
            $chatId = (string)($message['chat']['id'] ?? 0);
            $prefs['lastUpdateId'] = max($maxId, $update['update_id'] ?? 0);
            $messages[$chatId] = $message;
        }

        $total = 0;
        foreach ($messages as $chatId => $message) {
            $chatId = (int) $chatId;
            $text = $message['text'] ?? '';
            $msgId = (int) ($message['message_id'] ?? 0);
            if (in_array($chatId, $prefs['chats'])) {
                $api->sendMessage([
                    'chat_id' => $chatId,
                    'text' => __("notifications.already_active")
                ]);
            }
            else if ($text === '/start') {
                $api->sendMessage([
                    'chat_id' => $chatId,
                    'text' => __("notifications.please_send")
                ]);
            }
            else if ($code === null) {
                $api->sendMessage([
                    'chat_id' => $chatId,
                    'text' => __("notifications.code_not_set")
                ]);
            }
            else if ($codeExpires < time()) {
                $api->sendMessage([
                    'chat_id' => $chatId,
                    'text' => __("notifications.code_expired")
                ]);
            }
            else if ($text == $code) {
                $prefs['chats'][] = $chatId;
                $api->sendMessage([
                    'chat_id' => $chatId,
                    'reply_to_message_id' => $msgId,
                    'text' => __("notifications.activated")
                ]);
                $total++;
                $this->response->data = [
                    'total' => $total,
                    'msg' => __("notifications.activated")
                ];
            }
            else {
                $api->sendMessage([
                    'chat_id' => $chatId,
                    'reply_to_message_id' => $msgId,
                    'text' => __("notifications.code_wrong")
                ]);
            }
        }

        Config::saveDomain(NotificationsExtension::domain, $prefs);
    }

}
