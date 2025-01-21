<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class ApiRequest
{
    public $path;
    public $method;
    public $contentType;
    public $jsonBody;

    function __construct() {
        if (defined('MTT_API_USE_PATH_INFO')) {
            $this->path = $_SERVER['PATH_INFO'];
        }
        else {
            $this->path = $_GET['_path'] ?? '';
        }
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

    function content(string $contentType, string $content, int $code = 200)
    {
        $this->contentType = $contentType;
        $this->data = $content;
        $this->code = $code;
        return $this;
    }

    function htmlContent(string $content, int $code = 200): ApiResponse
    {
        return $this->content('text/html', $content, $code);
    }

    function cssContent(string $content, int $code = 200): ApiResponse
    {
        return $this->content('text/css', $content, $code);
    }

    function  exit()
    {
        if (is_null($this->data) && is_null($this->code)) {
            http_response_code(404);
        }
        if (!is_null($this->code)) {
            http_response_code($this->code);
        }
        if ($this->contentType != 'application/json') {
            header('Content-type: '. $this->contentType);
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



abstract class MTTExtension
{
    const bundleId = ''; //abstract

    function init() {
    }

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

    public static function extApiActionUrl(string $action, ?string $params = null)
    {
        $url = get_unsafe_mttinfo('api_url'). 'ext/'. static::bundleId. "/$action";
        if (!is_null($params)) {
            if (false !== strpos($url, '?')) {
                $url .= '&'. $params;
            }
            else {
                $url .= '?'. $params;
            }
        }
        return $url;
    }

}

interface MTTHttpApiExtender
{
    function extendHttpApi(): array;
}

interface MTTExtensionSettingsInterface
{
    function settingsPage(): string;
    function settingsPageType(): int;
    function saveSettings(array $array, ?string &$outMesssage): bool;
}


class MTTExtensionLoader
{
    private static $exts = [];

    public static function loadExtension(string $ext)
    {
        if (isset(self::$exts[$ext])) {
            error_log("Extension '$ext' is already registered");
            return;
        }

        $loader = MTT_EXT. $ext. '/loader.php';
        if (!file_exists($loader)) {
            error_log("Failed to init extension '$ext': no loader.php");
            return;
        }

        require_once(MTT_EXT. $ext. '/loader.php');
        $getInstance = 'mtt_ext_'. $ext. '_instance';

        if (!function_exists($getInstance)) {
            throw new Exception("Failed to init extension '$ext': no '$getInstance' function");
        }

        $instance = $getInstance();
        if ( ! ($instance instanceof MTTExtension) ) {
            throw new Exception("Failed to init extension '$ext': incompatible instance");
        }

        $className = get_class($instance);
        if (!defined("$className::bundleId")) {
            throw new Exception("Failed to load extension '$ext': missing required class constants (bundleId)");
        }
        if ($instance::bundleId != $ext) {
            throw new Exception("Failed to load extension '$ext': bundleId does not conforms to extension dir");
        }

        Lang::instance()->loadExtensionLang($ext);

        $instance->init();
        self::$exts[$ext] = $instance;
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
     * @return string[]
     */
    public static function bundles(): array
    {
        $lang = Lang::instance();
        $a = [];
        $files = array_diff(scandir(MTT_EXT) ?? [], ['.', '..']);
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

    public static function extensionInstance(string $ext): ?MTTExtension
    {
        return self::$exts[$ext] ?? null;
    }

    public static function isLoaded(string $ext): bool
    {
        return isset(self::$exts[$ext]);
    }
}
