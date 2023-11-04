<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
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
require_once(MTTINC. 'api/ExtSettingsController.php');

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
        'POST'    => [ ListsController::class , 'putId' ], //compatibility
    ],
    '/tasks' => [
        'GET'  => [ TasksController::class , 'get' ],
        'POST' => [ TasksController::class , 'post' ],
        'PUT'  => [ TasksController::class , 'put' ],
    ],
    '/tasks/(-?\d+)' => [
        'PUT'     => [ TasksController::class , 'putId' ],
        'DELETE'  => [ TasksController::class , 'deleteId' ],
        'POST'    => [ TasksController::class , 'putId' ], //compatibility
    ],
    '/tasks/parseTitle' => [
        'POST' => [ TasksController::class , 'postTitleParse' ],
    ],
    '/tasks/newCounter' => [
        'POST' => [ TasksController::class , 'postNewCounter' ],
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
    '/ext-settings/(.+)' => [
        'GET'     => [ ExtSettingsController::class , 'get' ],
        'PUT'     => [ ExtSettingsController::class , 'put' ],
        'POST'    => [ ExtSettingsController::class , 'put' ], //compatibility
    ]
);

// look for extensions
foreach (MTTExtensionLoader::loadedExtensions() as $instance) {
    if ($instance instanceof MTTHttpApiExtender) {
        $newRoutes = $instance->extendHttpApi();
        foreach ($newRoutes as $endpoint => $methods) {
            $endpoint = '/ext/'. $instance::bundleId. $endpoint;
            foreach ($methods as $k => &$v) {
                $v[3] = true; // Mark extension method
            }
            $endpoints[$endpoint] = $methods;
        }
    }
}

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
            $response->htmlContent("Unknown method for resource", 500)
                ->exit();
        }
        if ( !is_array($classDescr) || count($classDescr) < 2) {
            $response->htmlContent("Incorrect method definition", 500)
                ->exit();
        }
        // check if class method exists
        $class = $classDescr[0];
        $classMethod = $classDescr[1];
        $isExtMethod = $classDescr[3] ?? false;
        if ($isExtMethod) {
            if (false == ($classDescr[2] ?? false)) { //TODO: describe $classDescr[2]
                // By default all extension methods require write access rights
                checkWriteAccess();
            }
        }
        $param = null;
        if (count($m) >= 2) {
            $param = $m[1];
        }
        if (method_exists($class, $classMethod)) { // test for static with ReflectionMethod?
            if ($req->method != 'GET' && $req->contentType == 'application/json') {
                if ($req->decodeJsonBody() === false) {
                    $response->htmlContent("Failed to parse JSON body", 500)
                        ->exit();
                }
            }
            $instance = new $class($req, $response);
            $instance->$classMethod($param);
            $executed = true;
            break;
        }
        else {
            if (MTT_DEBUG) {
                $response->htmlContent("Class method $class:$classMethod() not found", 405)
                    ->exit();
            }
            $response->htmlContent("Class method not found", 405)
                ->exit();
        }
    }
}

if (!$executed) {
    if (MTT_DEBUG) {
        $response->htmlContent("Unknown endpoint: {$req->method} {$req->path}", 404)
            ->exit();
    }
    $response->htmlContent("Unknown endpoint", 404);
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
