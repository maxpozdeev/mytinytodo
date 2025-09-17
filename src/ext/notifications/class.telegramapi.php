<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace Notify;

class TelegramApi
{
    private $token = '';
    /** @var ?array $lastError */
    public $lastError = null;
    public $logApiErrors = false;
    public $throwExceptionOnApiError = false;

    function __construct(string $token)
    {
        $this->token = $token;
    }

    function getMe(): array
    {
        return $this->makeGetRequest('getMe');
    }

    function getUpdates(?array $params = null): array
    {
        return $this->makePostRequest('getUpdates', $params ?? []);
    }

    function sendMessage(array $params): array
    {
        return $this->makePostRequest('sendMessage', $params);
    }

    private function makeGetRequest(string $method): array
    {
        $options = array(
            'http' => array(
                'ignore_errors' => true
            )
        );
        $context = stream_context_create($options);
        $this->lastError = null;
        $body = $err = null;
        set_error_handler(function ($errno, $message, $file, $line) {
            throw new \ErrorException($message, $errno, $errno, $file, $line);
        });
        try {
            $body = @file_get_contents('https://api.telegram.org/bot'. $this->token .'/'. $method, false, $context);
        }
        catch (\Exception $e) {
            $err = boolval(ini_get('html_errors')) ?  htmlspecialchars_decode($e->getMessage()) : $e->getMessage();
        }
        restore_error_handler();
        if ($body === false || null !== $err) {
            $msg = "Failed to make request to Telegram API ($method)". ($err ? ": $err" : "");
            if ($this->logApiErrors) {
                error_log($msg);
            }
            throw new \Exception($msg);
        }
        $decodedBody = $this->decodeBody($body, $method);
        return $decodedBody['result'] ?? [];
    }

    private function makePostRequest(string $method, array $params): array
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
        $body = $err = null;
        set_error_handler(function ($errno, $message, $file, $line) {
            throw new \ErrorException($message, $errno, $errno, $file, $line);
        });
        try {
            $body = @file_get_contents('https://api.telegram.org/bot'. $this->token .'/'. $method, false, $context);
        }
        catch (\Exception $e) {
            $err = boolval(ini_get('html_errors')) ?  htmlspecialchars_decode($e->getMessage()) : $e->getMessage();
        }
        restore_error_handler();
        if ($body === false || null !== $err) {
            $msg = "Failed to make request to Telegram API ($method)". ($err ? ": $err" : "");
            if ($this->logApiErrors) {
                error_log($msg);
            }
            throw new \Exception($msg);
        }
        $decodedBody = $this->decodeBody($body, $method);
        return $decodedBody['result'] ?? [];
    }

    private function decodeBody(string $body, string $method = ''): array
    {
        $decodedBody = json_decode($body, true);
        if (!is_array($decodedBody)) {
            $decodedBody = [];
        }
        if (!isset($decodedBody['ok'])) {
            throw new \Exception("Telegram API ($method) Error");
        }
        if ($decodedBody['ok'] === false) {
            $this->lastError = [
                'error_code' => $decodedBody['error_code'] ?? 0,
                'description' => ($decodedBody['description'] ?? '')
            ];
            if ($this->logApiErrors) {
                error_log("Telegram API ($method) Error ". $this->lastError['error_code']. "): ". $this->lastError['description']);
            }
            if ($this->throwExceptionOnApiError) {
                throw new \Exception("Telegram API ($method) Error ". $this->lastError['error_code']. ": ". $this->lastError['description']);
            }
        }
        return $decodedBody;
    }
}
