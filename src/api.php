<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2026 Max Pozdeev <maxpozdeev@gmail.com>
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

const MTT_API_ENDPOINT_OWNER_GENERAL = 0;
const MTT_API_ENDPOINT_OWNER_EXTENSION = 1;
const MTT_API_ENDPOINT_OWNER_CONTROLPANEL = 2;

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
    '/user/([^/]+)/lists' => [
        'GET'  => [ ListsController::class , 'get' ]    # lists of specific user
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
        'POST' => [ TasksController::class , 'postCounterOfNewTasks' ],
    ],
    '/tagCloud/(-?\d+)' => [
        'GET'  => [ TagsController::class , 'getCloud' ],
    ],
    '/suggestTags' => [
        'GET'  => [ TagsController::class , 'getSuggestions' ],
    ],
    '/(login|logout|session|resetPassword|newPassword)' => [
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
                // Mark as extension method
                $v[3] = MTT_API_ENDPOINT_OWNER_EXTENSION;
            }
            $endpoints[$endpoint] = $methods;
        }
    }
}


$req = ApiRequest::instance();

# All API requests have to check a CSRF token, except only this. //TODO: re-make
if ($req->path !== '/session') {
    check_token();
}

# Control Panel API routes (lazy loading of classes)
if (substr($req->path, 0, 4) === '/cp/') {
    if (defined('MTT_DEMO')) {
        (new JsonApiResponse([ 'ok'=>true, 'total' => 1, 'msg' => __('demo_mode', true), 'alertText' => __('demo_mode', true) ], 200))->exit();
    }
    ControlPanelApiController::mergeEndpoints($endpoints);
}

$req->username = ''; //FIXME: !!!
$req->setUserId( userId() ?? 0 );

$response = new ApiResponse();
$executed = false;
$data = null;

foreach ($endpoints as $search => $methods) {
    $m = array();
    if (!preg_match("#^$search$#", $req->path, $m)) {
        continue;
    }

    $classDescr = $methods[$req->method] ?? null;
    // check if http method is supported for path
    if ( is_null($classDescr) ) {
        (new ErrorApiResponse("Unknown method for resource", 500))->exit();
    }
    if ( !is_array($classDescr) || count($classDescr) < 2) {
        (new ErrorApiResponse("Incorrect method definition", 500))->exit();
    }

    // check if class method exists
    $class = $classDescr[0];
    $classMethod = $classDescr[1];
    $endpointOwner = $classDescr[3] ?? MTT_API_ENDPOINT_OWNER_GENERAL;
    if (MTT_API_ENDPOINT_OWNER_EXTENSION === $endpointOwner) {
        if (false == ($classDescr[2] ?? false)) { //TODO: describe $classDescr[2]
            // By default all extension methods require write access rights
            checkWriteAccess();
        }
    }
    else if (MTT_API_ENDPOINT_OWNER_CONTROLPANEL === $endpointOwner) {
        if (!is_logged() || !is_admin()) {
            (new ErrorApiResponse("Access denied. Admin only.", 403))->exit();
        }
    }

    // method can get one argument //TODO: pass args via ApiRequest
    $param = null;
    if (count($m) >= 2) {
        $param = $m[1];
    }

    // call it
    if (method_exists($class, $classMethod)) { // test for static with ReflectionMethod?
        if ($req->method != 'GET' && $req->contentType == 'application/json') {
            if ($req->decodeJsonBody() === false) {
                (new ErrorApiResponse("Failed to parse JSON body", 500))->exit();
            }
        }
        $instance = new $class($req, $response);
        $instance->$classMethod($param);
        $executed = true;
        break;
    }
    else {
        if (MTT_DEBUG) {
            (new ErrorApiResponse("Class method $class:$classMethod() not found", 405))->exit();
        }
        (new ErrorApiResponse("Class method not found", 405))->exit();
    }

}

if (!$executed) {
    if (MTT_DEBUG) {
        (new ErrorApiResponse("Unknown endpoint: {$req->method} {$req->path}", 404))->exit();
    }
    (new ErrorApiResponse("Unknown endpoint", 404))->exit();
}
$response->exit();



function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    if ($errno==E_ERROR || $errno==E_CORE_ERROR || $errno==E_COMPILE_ERROR || $errno==E_USER_ERROR || $errno==E_PARSE) {
        $error = 'Error';
    }
    elseif ($errno==E_WARNING || $errno==E_CORE_WARNING || $errno==E_COMPILE_WARNING || $errno==E_USER_WARNING) {
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
    if (is_null($listId)) {
        $req = ApiRequest::instance();
        if (!$req->userId() && !is_logged())
            ErrorApiResponse::exitWithMessage(__("denied"), 403);
    }
    else if ($listId === -1) {
        if (!is_logged())
            ErrorApiResponse::exitWithMessage(__("denied"), 403);
    }
    else
    {
        $repo = new ListRepo(DBConnection::instance());
        $list = $repo->findRealListById($listId);
        if (!$list) {
            if (is_logged())
                ErrorApiResponse::exitWithMessage(__("listNotFound"), 404);
            else
                ErrorApiResponse::exitWithMessage(__("denied"), 403);
        }
        if (!canReadList($list))
            ErrorApiResponse::exitWithMessage(__("denied"), 403);
    }
}

function checkWriteAccess(?int $listId = null)
{
    if (haveWriteAccess($listId))
        return;
    (new JsonApiResponse([ 'ok'=>false, 'total'=>0, 'list'=>[], 'denied'=>1 ], 403))->exit();
}

function checkAndGetListForWrite(int $listId): AbstractTaskList
{
    $repo = new ListRepo(DBConnection::instance());

    $list = ($listId === -1) ? $repo->alltasksListByUserId(userId()) : $repo->findRealListById($listId);
    if (!$list) {
        if (is_logged())
            ErrorApiResponse::exitWithMessage(__("listNotFound"), 404);
        else
            ErrorApiResponse::exitWithMessage(__("denied"), 403);
    }
    if (!canWriteToList($list))
        ErrorApiResponse::exitWithMessage(__("denied"), 403);

    return $list;
}

function haveWriteAccess(?int $listId = null) : bool
{
    if (!is_logged())
        return false;

    # currently a logged user have write access to own lists only
    $req = ApiRequest::instance();
    $reqUserId = $req->userId();
    if (!$reqUserId || userId() != $reqUserId)
        return false;

    // check list exist
    if ($listId !== null && $listId != -1)
    {
        $db = DBConnection::instance();
        $count = $db->sq("SELECT COUNT(*) FROM {$db->prefix}lists WHERE id=? AND user_id=?",
            array($listId, $reqUserId));
        if (!$count)
            return false;
    }
    return true;
}


class ControlPanelApiController
{
    /**
     *
     * @return array<MTTControlPanelHttpApiExtender>
     */
    static function registeredClasses(): array
    {
        require_once(MTTINC. 'api/BackupController.php');

        return [
            Backup\BackupController::class
        ];
    }

    static function mergeEndpoints(array &$a)
    {
        foreach (self::registeredClasses() as $class)
        {
            if ( ! is_a($class, MTTControlPanelHttpApiExtender::class, true) ) {
                continue;
            }

            $endpoints = $class::extendControlPanelHttpApi();
            foreach ($endpoints as $endpoint => $methods) {
                $endpoint = '/cp'. $endpoint;
                // Mark as control panel methods (admin check needed)
                foreach ($methods as $k => &$v) {
                    $v[3] = MTT_API_ENDPOINT_OWNER_CONTROLPANEL;
                }
                $a[$endpoint] = $methods;
            }
        }
    }

}
