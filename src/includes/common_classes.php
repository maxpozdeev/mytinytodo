<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class ApiRequest
{
    public $path;
    public $method;
    public $contentType;
    public $jsonBody;

    function __construct() {
        $this->path = $_SERVER['PATH_INFO'] ?? '';
        $this->method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    }

    function decodeJsonBody() {
        $this->jsonBody = json_decode( file_get_contents('php://input'), true, 10, JSON_INVALID_UTF8_SUBSTITUTE );
        return $this->jsonBody;
    }
}

class ApiResponse
{
    public $data = null;
    public $contentType = 'application/json';
    public $code = null;

    function htmlContent(string $content, int $code = 200): ApiResponse
    {
        $this->contentType = 'text/html';
        $this->data = $content;
        $this->code = $code;
        return $this;
    }

    function  exit()
    {
        if (is_null($this->data) && is_null($this->code)) {
            http_response_code(404);
        }
        if (!is_null($this->code)) {
            http_response_code($this->code);
        }
        if ($this->contentType == 'text/html') {
            print $this->data;
            exit();
        }
        jsonExit($this->data);
    }
}

abstract class ApiController
{
    /** @var ApiRequest */
    protected $req;

    /** @var ApiResponse */
    protected $response;

    function __construct(ApiRequest $req, ApiResponse $response) {
        $this->req = $req;
        $this->response = $response;
    }
}
