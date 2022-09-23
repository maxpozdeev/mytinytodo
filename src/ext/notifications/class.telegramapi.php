<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace Notify;

class TelegramApi
{
    private $token = '';
    /** @var ?array $lastError */
    public $lastError = null;

    function __construct(string $token)
    {
        $this->token = $token;
    }

    function getMe(): ?array
    {
        return $this->makeGetRequest('getMe');
    }

    function getUpdates(?array $params = null): ?array
    {
        return $this->makePostRequest('getUpdates', $params ?? []);
    }

    function sendMessage(array $params): ?array
    {
        return $this->makePostRequest('sendMessage', $params);
    }

    private function makeGetRequest(string $method): ?array
    {
        $this->lastError = null;
        $body = @file_get_contents('https://api.telegram.org/bot'. $this->token .'/'. $method, false);
        if ($body === false) {
            throw new \Exception("Failed to make request to Telegram API");
        }
        $decodedBody = $this->decodeBody($body);
        return $decodedBody['result'] ?? [];
    }

    private function makePostRequest(string $method, array $params): ?array
    {
        $json = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => $json,
                'ignore_errors' => true
            )
        );
        $context  = stream_context_create($options);
        $this->lastError = null;
        $body = @file_get_contents('https://api.telegram.org/bot'. $this->token .'/'. $method, false, $context);
        if ($body === false) {
            throw new \Exception("Failed to make request to Telegram API");
        }
        $decodedBody = $this->decodeBody($body);
        return $decodedBody['result'] ?? [];
    }

    private function decodeBody(string $body): array
    {
        $decodedBody = json_decode($body, true);
        if (!is_array($decodedBody)) {
            $decodedBody = [];
        }
        if (!isset($decodedBody['ok'])) {
            throw new \Exception("Telegram API Error");
        }
        if ($decodedBody['ok'] === false) {
            $this->lastError = [
                'error_code' => $decodedBody['error_code'] ?? 0,
                'description' => ($decodedBody['description'] ?? '')
            ];
        }
        return $decodedBody;
    }
}
