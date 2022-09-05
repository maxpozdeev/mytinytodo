<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

require_once('./init.php');

if (MTT_DEBUG) {
    set_error_handler('myErrorHandler'); //catch Notices, Warnings
    set_exception_handler('myExceptionHandler');
}
else {
    ini_set('display_errors', '0');
}

require_once(MTTINC. 'api/ListsController.php');
require_once(MTTINC. 'api/TasksController.php');
require_once(MTTINC. 'api/TagsController.php');
require_once(MTTINC. 'api/AuthController.php');

$endpoints = array(
    '/lists' => [
        'GET'  => [ ListsController::class , 'get' ],
        'POST' => [ ListsController::class , 'post' ],
        'PUT'  => [ ListsController::class , 'put' ],
    ],
    '/lists/(-?\d+)' => [
        'GET'     => [ ListsController::class , 'getId' ],
        'PUT'     => [ ListsController::class , 'putId' ],
        'DELETE'  => [ ListsController::class , 'deleteId' ],
    ],
    '/tasks' => [
        'GET'  => [ TasksController::class , 'get' ],
        'POST' => [ TasksController::class , 'post' ],
        'PUT'  => [ TasksController::class , 'put' ],
    ],
    '/tasks/(-?\d+)' => [
        'PUT'     => [ TasksController::class , 'putId' ],
        'DELETE'  => [ TasksController::class , 'deleteId' ],
    ],
    '/tasks/parseTitle' => [
        'POST' => [ TasksController::class , 'postTitleParse' ],
    ],
    '/tagCloud/(-?\d+)' => [
        'GET'  => [ TagsController::class , 'getCloud' ],
    ],
    '/suggestTags' => [
        'GET'  => [ TagsController::class , 'getSuggestions' ],
    ],
    '/(login|logout|session)' => [
        'POST' => [ AuthController::class , 'postAction' ],
    ],
);

$req = new ApiRequest();
$response = new ApiResponse();
$executed = false;
$data = null;

foreach ($endpoints as $search => $methods) {
    $m = array();
    if (preg_match("#^$search$#", $req->path, $m)) {
        $classDescr = $methods[$req->method] ?? null;
        // check if http method is supported for path
        if ( is_null($classDescr) ) {
            $response->htmlContent("Unknown method for resource", 500)->exit();
        }
        if ( !is_array($classDescr) || count($classDescr) != 2) {
            $response->htmlContent("Incorrect method definition", 500)->exit();
        }
        // check if class method exists
        $class = $classDescr[0];
        $classMethod = $classDescr[1];
        $param = null;
        if (count($m) >= 2) {
            $param = $m[1];
        }
        if (method_exists($class, $classMethod)) { // test for static with ReflectionMethod?
            if ($req->method != 'GET' && $req->contentType == 'application/json') {
                if ($req->decodeJsonBody() === false) {
                    $response->htmlContent("Failed to parse JSON body", 500)->exit();
                }
            }
            $instance = new $class($req, $response);
            $instance->$classMethod($param);
            $executed = true;
            break;
        }
        else {
            if (MTT_DEBUG) {
                $response->htmlContent("Class method $class:$classMethod() not found", 405)->exit();
            }
            $response->htmlContent("Class method not found", 405)->exit();
        }
    }
}

if (!$executed) {
    $response->htmlContent("Unknown command", 404);
}
$response->exit();



function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    if ($errno==E_ERROR || $errno==E_CORE_ERROR || $errno==E_COMPILE_ERROR || $errno==E_USER_ERROR || $errno==E_PARSE) {
        $error = 'Error';
    }
    elseif ($errno==E_WARNING || $errno==E_CORE_WARNING || $errno==E_COMPILE_WARNING || $errno==E_USER_WARNING || $errno==E_STRICT) {
        if (error_reporting() & $errno) $error = 'Warning'; else return;
    }
    elseif ($errno==E_NOTICE || $errno==E_USER_NOTICE || $errno==E_DEPRECATED || $errno==E_USER_DEPRECATED) {
        if (error_reporting() & $errno) $error = 'Notice'; else return;
    }
    else $error = "Error ($errno)"; // here may be E_RECOVERABLE_ERROR
    throw new Exception("$error: '$errstr' in $errfile:$errline", -1);
}

function myExceptionHandler(Throwable $e)
{
    // to avoid Exception thrown without a stack frame
    try
    {
        if (-1 == $e->getCode()) {
            //thrown in myErrorHandler
            http_response_code(500);
            logAndDie( $e->getMessage() );
        }

        $c = get_class($e);
        $errText = "Exception ($c): '". $e->getMessage(). "' in ". $e->getFile(). ":". $e->getLine() ;

        if (MTT_DEBUG) {
            if ( count($e->getTrace()) > 0 ) {
                $errText .= "\n". $e->getTraceAsString() ;
            }
        }
        http_response_code(500);
        logAndDie($errText);
    }
    catch (Exception $e) {
        http_response_code(500);
        logAndDie('Exception in ExceptionHandler: \''. $e->getMessage() .'\' in '. $e->getFile() .':'. $e->getLine());
    }
    exit;
}

function checkReadAccess(?int $listId = null)
{
    check_token();
    $db = DBConnection::instance();
    if (is_logged()) return true;
    if ($listId !== null)
    {
        $id = $db->sq("SELECT id FROM {$db->prefix}lists WHERE id=? AND published=1", array($listId));
        if ($id) return;
    }
    jsonExit( array('total'=>0, 'list'=>array(), 'denied'=>1) );
}

function checkWriteAccess(?int $listId = null)
{
    check_token();
    if (haveWriteAccess($listId)) return;
    http_response_code(403);
    jsonExit( array('total'=>0, 'list'=>array(), 'denied'=>1) );
}

function haveWriteAccess(?int $listId = null) : bool
{
    if (is_readonly()) {
        return false;
    }
    // check list exist
    if ($listId !== null && $listId != -1)
    {
        $db = DBConnection::instance();
        $count = $db->sq("SELECT COUNT(*) FROM {$db->prefix}lists WHERE id=?", array($listId));
        if (!$count) return false;
    }
    return true;
}


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
