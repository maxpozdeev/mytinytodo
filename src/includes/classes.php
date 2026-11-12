<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class ApiRequest
{
    protected static $instance;

    public $path;
    public $method;
    public $contentType;
    public $jsonBody;
    protected string $targetUsername = '';  # owner of requested data (not the session user)
    protected ?int $targetUserId = null;    # id of the owner of requested data, null when not specified
    protected bool $targetExplicit = false;

    public static function instance() : ApiRequest
    {
        if (!isset(self::$instance)) {
            self::$instance = new ApiRequest();
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    function __construct() {
        $this->path = self::getApiPath();
        $this->method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    }

    function decodeJsonBody() {
        $this->jsonBody = json_decode( file_get_contents('php://input'), true, 10, JSON_INVALID_UTF8_SUBSTITUTE );
        if (!is_array($this->jsonBody)) {
            $this->jsonBody = [];
        }
        return $this->jsonBody;
    }

    /**
     * Id of the owner of the requested data.
     * Falls back to the current authernticated user when no target user was specified explicitly.
     */
    function ownerUserId(): int
    {
        return $this->targetUserId ?? (userId() ?? 0);
    }

    /**
     * Id of the explicitly requested user.
     * null when no target user was specified.
     */
    function targetUserId(): ?int
    {
        return $this->targetUserId;
    }

    function targetUsername(): string
    {
        return (string)$this->targetUsername;
    }

    function isTargetExplicit(): bool
    {
        return $this->targetExplicit;
    }

    /**
     * Determines whether the current user (viewer) owns the requested data.
     *
     * Always returns false for guests.
     *
     * If the route targets a specific user — via the URI /user/{username}
     * or the query parameter user={username} — returns true only when
     * {username} matches the authenticated user (i.e. the viewer owns
     * the resource); otherwise returns false.
     *
     * If the route has no user target, returns true for an authenticated user.
     *
     * {@see ApiController::resolveTargetUser}
     */
    function isOwnerOfTarget(): bool
    {
        return is_logged() && $this->ownerUserId() === userId();
    }

    function setTargetUser(?int $id, string $username = '')
    {
        $this->targetUserId = $id;
        $this->targetExplicit = ($id !== null);
        $this->targetUsername = $username;
    }

    function resetTargetUser(): void
    {
        $this->targetUserId = null;
        $this->targetExplicit = false;
        $this->targetUsername = '';
    }

    static function getApiPath(): string
    {
        if (!MTT_USE_REWRITE) {
            if (isset($_GET['_path'])) {
                $path = $_GET['_path'];
                if ($path === '' || $path[0] !== '/')
                    return '/'. $path;
                return $path;
            }
            return '/';
        }
/*
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');                       # e.g. /var/www/html
        $scriptName = substr($_SERVER['SCRIPT_FILENAME'], strlen($docRoot));    # e.g. /var/www/html/mtt/api.php --> /mtt/api.php
        $uri = url_dir($scriptName). 'api/';                                    # /mtt/api/
*/
        #$uri = get_unsafe_mttinfo('mtt_uri'). 'api/';
        $uri = apiMakeUrl('', null, false);
        $path = $_SERVER['REQUEST_URI'] ?? '';
        if (false !== $p = strpos($path, '?')) {
            $path = substr($path, 0, $p);
        }
        if ($path !== '' && 0 === strncmp($path, $uri, strlen($uri))) {
            $path = substr($path, strlen($uri) -1);
        }
        return $path;
    }
}

class ApiResponse
{
    public $data = null;
    public $contentType = 'application/json';
    public $code = null;

    // deprecated
    function htmlContent(string $content): void
    {
        $this->contentType = 'text/html; charset=utf-8';
        $this->data = $content;
        $this->code = 200;
    }

    // deprecated, use ErrorApiResponse
    function errorJsonContent(string $errorMessage, int $code): void
    {
        $this->data = [
            'ok' => false,
            'error' => htmlspecialchars($errorMessage)
        ];
        $this->code = $code;
    }

    function exit()
    {
        if (is_null($this->data) && is_null($this->code)) {
            http_response_code(404);
        }
        if (!is_null($this->code)) {
            http_response_code($this->code);
        }
        if ($this->contentType === 'application/json') {
            header('Content-type: application/json; charset=utf-8');
            echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES). "\n";
        }
        else {
            header('Content-type: '. $this->contentType);
            echo $this->data;
        }
        try {
            MTTNotificationCenter::postDidFinishRequestNotification();
        }
        catch (Throwable $e) {
            error_log('postDidFinishRequestNotification: '. $e->getMessage());
        }
        exit;
    }
}

class JsonApiResponse extends ApiResponse
{
    function __construct(array $a, int $code = 200)
    {
        $this->code = $code;
        $this->contentType = 'application/json';
        $this->data = $a;
    }
}

class ErrorApiResponse extends JsonApiResponse
{
    function __construct(string $errorMessage, int $code = 500)
    {
        $this->data = [
            'ok' => false,
            'error' => $errorMessage
        ];
        $this->code = $code;
    }

    static function exitWithMessage(string $errorMessage, int $code = 500)
    {
        (new static($errorMessage, $code))->exit();
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

    /**
     * Resolve an optional /user/{username}/... route parameter and set it as
     * the owner of the requested data.
     *
     * Returns false and sets a 404 response when the user cannot be resolved.
     */
    protected function resolveTargetUser(?string $username, bool $required = false): bool
    {
        $this->req->resetTargetUser();

        $username = trim((string)$username);
        if ($username === '') {
            if ($required) {
                $this->response->errorJsonContent("User not found", 404);
                return false;
            }
            return true;
        }

        $sessionUsername = username();
        if ($sessionUsername !== null && $username === $sessionUsername) {
            $targetUserId = userId();
        }
        else {
            $targetUserId = (new UserRepo(DBConnection::instance()))->findUserIdByUsername($username);
        }

        if (!$targetUserId || (!need_auth() && $targetUserId !== userId())) {
            $this->response->errorJsonContent("User not found", 404);
            return false;
        }

        $this->req->setTargetUser((int)$targetUserId, $username);
        return true;
    }
}



abstract class MTTExtension
{
    const bundleId = ''; //abstract

    function init() {
    }

    /**
     * @param string $ext
     * @return null|array
     */
    public static function extMetaInfo(string $ext): ?array
    {
        $file = MTT_EXT. $ext. '/extension.json';
        if ( file_exists($file)
            && false !== ($json = file_get_contents($file))
            && ($meta = json_decode($json, true))
            && is_array($meta) )
        {
            // check mandatory keys
            if (!isset($meta['bundleId']) || !isset($meta['name']) || !isset($meta['version']) || !isset($meta['description'])) {
                return null;
            }
            if (!is_string($meta['bundleId']) || !is_string($meta['name']) || !is_string($meta['version']) || !is_string($meta['description'])) {
                return null;
            }
            return $meta;
        }
        error_log("$ext/extension.json is missing or invalid");
        return null;
    }

    public static function extApiActionUrl(string $action, ?array $params = null): string
    {
        $url = apiMakeUrl('ext/'. static::bundleId. "/$action", $params);
        return $url;
    }

    public static function getFileVer(string $filename): string
    {
        return (string)get_filever('ext', $filename, static::bundleId);
    }

    public static function getFileUri(string $filename, bool $versioned = true): string
    {
        $version = ($versioned) ? '?v='. htmlspecialchars(static::getFileVer($filename)) : '';
        return get_mttinfo('mtt_uri'). htmlspecialchars('ext/'. static::bundleId. '/'. $filename) . $version;
    }

}

interface MTTHttpApiExtender
{
    function extendHttpApi(): array;
}

interface MTTControlPanelHttpApiExtender
{
    static function extendControlPanelHttpApi(): array;
}

interface MTTExtensionSettingsInterface
{
    function settingsPage(): string;
    function settingsPageType(): int;
    function saveSettings(array $array, ?string &$outMesssage): bool;
}

class MTTExtensionLoaderException extends Exception {}

class MTTExtensionLoader
{
    private static $exts = [];

    /**
     *
     * @throws Exception
     */
    public static function loadExtension(string $ext): bool
    {
        if (isset(self::$exts[$ext])) {
            error_log("Extension '$ext' is already registered");
            return false;
        }

        $loader = MTT_EXT. $ext. '/loader.php';
        if (!file_exists($loader)) {
            error_log("Failed to init extension '$ext': no loader.php");
            return false;
        }

        require_once(MTT_EXT. $ext. '/loader.php');
        $extNormalized = str_replace('-', '_', $ext);
        $instanceFunc = 'mtt_ext_'. $extNormalized. '_instance';

        if (!function_exists($instanceFunc)) {
            throw new MTTExtensionLoaderException("Failed to init extension '$ext': no '$instanceFunc' function");
        }

        $instance = $instanceFunc();
        if ( ! ($instance instanceof MTTExtension) ) {
            throw new MTTExtensionLoaderException("Failed to init extension '$ext': incompatible instance");
        }

        $className = get_class($instance);
        if (!defined("$className::bundleId")) {
            throw new MTTExtensionLoaderException("Failed to load extension '$ext': missing required class constants (bundleId)");
        }
        if ($instance::bundleId != $ext) {
            throw new MTTExtensionLoaderException("Failed to load extension '$ext': bundleId does not conforms to extension dir");
        }

        Lang::instance()->loadExtensionLang($ext);

        $instance->init();
        self::$exts[$ext] = $instance;

        return true;
    }

    /**
     * @return MTTExtension[]
     */
    public static function loadedExtensions(): array
    {
        $a = [];
        foreach (self::$exts as $ext => $instance) {
            $a[] = $instance;
        }
        return $a;
    }

    /**
     * @return array<string, array>
     */
    public static function bundles(): array
    {
        $lang = Lang::instance();
        $a = [];
        $files = array_diff(scandir(MTT_EXT) ?: [], ['.', '..']);
        foreach ($files as $ext) {
            if ( !is_dir(MTT_EXT. $ext)
                || !file_exists(MTT_EXT. $ext. '/loader.php') ) {
                continue;
            }

            $meta = MTTExtension::extMetaInfo($ext);
            if (!$meta) {
                continue;
            }

            if ( $lang->langCode() != 'en'
                && null !== ($translation = $lang->getExtensionLang($ext))
                && null !== ($locName = $translation['ext.'.$ext.'.name'] ?? null) ) {
                $meta['name'] = $locName;
            }
            $a[$ext] = $meta;
        }
        return $a;
    }

    /**
     * @param array $meta
     * @return bool
     */
    public static function isBundleCompatible(array $meta): bool
    {
        $cpt = $meta['compatibility'] ?? '';
        if ($cpt === '' || !is_string($cpt) || strpos($cpt, ' - ') === false)
            return false;
        list($vmin, $vmax) = explode(' - ', $cpt, 2);

        if (version_compare(MTTVersion::VERSION, trim($vmin)) < 0)
            return false;
        if (version_compare(MTTVersion::VERSION, trim($vmax)) > 0)
            return false;
        return true;
    }

    public static function extensionInstance(string $ext): ?MTTExtension
    {
        return self::$exts[$ext] ?? null;
    }

    public static function isLoaded(string $ext): bool
    {
        return isset(self::$exts[$ext]);
    }
}
