<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace Notify;

use NotificationsExtension;
use MTTNotification;


class Sender
{
    private $prefs;
    private $cli = false;

    function __construct(array $prefs, bool $useCli = false)
    {
        $this->prefs = $prefs;
        if ($useCli && function_exists('pcntl_fork')) {
            $this->cli = true;
        }
    }

    function notify(array $item)
    {
        $notification = $item['notification'] ?? '';
        $object = $item['object'] ?? null;
        switch ($notification) {
            case MTTNotification::didCreateTask: $this->notifyTaskCreated($object); break;
            case MTTNotification::didCreateList: $this->notifyListCreated($object); break;
        }
    }

    /*
        $task['title'],  $task['tags'], $task['duedate'], $task['listName'] are already escaped
    */
    private function notifyTaskCreated($task)
    {
        $link = get_mttinfo('url'). '?task='. $task['id'];

        //email
        if (count($this->prefs['emails']) > 0) {
            $aText = [];
            $aText[] = "New task in ". htmlspecialchars_decode($task['listName']). ":";
            $aText[] = htmlspecialchars_decode($task['title']);
            $aText[] = "";
            if ($task['duedate'] != '') {
                $aText[] = "Due: ". htmlspecialchars_decode($task['duedate']);
            }
            if ($task['tags'] != '') {
                $aText[] = "Tags: ". implode(", ", preg_split("/,\s*/", htmlspecialchars_decode($task['tags']), -1, PREG_SPLIT_NO_EMPTY));
            }
            if ($aText[count($aText)-1] != '') {
                $aText[] = "";
            }
            $aText[] = "Link: $link";
            $text = implode("\r\n", $aText);

            $this->sendEmails( $text, "New task #". $task['id']);
        }

        // telegram
        if (count($this->prefs['chats']) > 0) {
            $text = "New task <a href=\"$link\">#". $task['id']. "</a> in ". $task['listName'] .": ". $task['title'];
            if ($task['duedate'] != '') {
                $text .= "\nDue: ". $task['duedate'];
            }
            if ($task['tags'] != '') {
                $text .= "\nTags: ". implode(", ", preg_split("/,\s*/", $task['tags'], -1, PREG_SPLIT_NO_EMPTY));
            }

            $this->sendTelegrams($text);
        }
    }

    /*
        $list['name'] is already escaped
    */
    private function notifyListCreated($list)
    {
        $link = get_mttinfo('url'). '?list='. $list['id'];

        //email
        if (count($this->prefs['emails']) > 0) {
            $aText = [];
            $aText[] = "New list:";
            $aText[] = htmlspecialchars_decode($list['name']);
            $aText[] = "";
            $aText[] = "Link: $link";
            $text = implode("\r\n", $aText);

            $this->sendEmails( $text, "New list");
        }

        // telegram
        if (count($this->prefs['chats']) > 0) {
            $text = "New list: <a href=\"$link\">". $list['name']. "</a>";

            $this->sendTelegrams($text);
        }
    }

    private function sendEmails(string $text, string $subject)
    {
        $host = parse_url(get_unsafe_mttinfo('url'), PHP_URL_HOST);
        $host = preg_replace('/^(www\.)/', '', $host);
        $fromAddr = "mytinytodo@$host";
        $from =  "myTinyTodo <$fromAddr>";
        $mttTitle =  str_replace( ["\r","\n"], '', get_unsafe_mttinfo('title') );
        $subject = "[$mttTitle] $subject";
        if (!mb_check_encoding($subject, 'ASCII')) {
            $subject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
        }
        $headers = [
            'From: '. $from
        ];
        if (mb_check_encoding($text, 'ASCII')) {
            $headers[] = 'Content-Type: text/plain';
        }
        else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: 8bit';
        }
        foreach ($this->prefs['emails'] as $email) {
            mail($email, $subject, $text, implode("\r\n", $headers), "-f$fromAddr");
        }
    }

    private function sendTelegrams(string $text)
    {
        if ($this->cli) {
            $this->sendTelegramsInBackground($text);
        }
        else {
            $this->sendTelegramsWithApi($text);
        }
    }

    // public!
    function sendTelegramsWithApi(string $text)
    {
        if (!isset($this->prefs['token'])) {
            return;
        }
        $api = new TelegramApi($this->prefs['token']);
        $api->logApiErrors = true;
        $blockedChats = [];
        foreach ($this->prefs['chats'] as $chatId) {
            // try-catch?
            $result = $api->sendMessage([
                'chat_id' => $chatId,
                'parse_mode' => 'HTML', //or MarkdownV2
                'text' => $text,
                'disable_web_page_preview' => true
            ]);
            if (!$result && $api->lastError && $api->lastError['error_code'] == 403) {
                // User has blocked the bot
                $blockedChats[] = $chatId;
                error_log("Bot is blocked in chat $chatId, chat will be deactivated");
            }
        }
        //We can remove blocked chats from settings
        if (count($blockedChats) > 0) {
            $this->prefs['chats'] = array_diff($this->prefs['chats'], $blockedChats);
            \Config::saveDomain(NotificationsExtension::domain, $this->prefs);
        }
    }

    private function sendTelegramsInBackground(string $text)
    {
        $hash = password_hash($this->prefs['token'], PASSWORD_DEFAULT);
        $dir = __DIR__;
        $outfile = ''; # or '> /dev/null 2>&1';
        //$outfile = '> /dev/null 2>&1';
        // if (MTT_DEBUG) {
        //     $outfile = "> $dir/../../db/cli-notify.log  2>&1";
        // }
        $fh = popen("php -f $dir/cli-notify.php $outfile", 'w');
        fwrite($fh, $hash."\n".$text);
        fclose($fh);
    }

}
