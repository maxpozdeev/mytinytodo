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



abstract class MTTExtension
{
    const bundleId = '';
    const name = '';
    abstract function init();
}

interface MTTHttpApiExtender
{
    function extendHttpApi(): array;
}

interface MTTExtensionSettingsInterface
{
    function settingsPage(): string;
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
        if (!defined("$className::bundleId") || !defined("$className::name")) {
            throw new Exception("Failed to register extension '$ext': require class constants (bundleId, name)");
        }
        if ($instance::bundleId != $ext) {
            throw new Exception("Extension '$ext' bundleId does not conforms to extension dir");
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
                || !file_exists(MTT_EXT. $ext. '/loader.php')
                || !file_exists(MTT_EXT. $ext. '/extension.json') ) {
                continue;
            }
            $jsonData = file_get_contents(MTT_EXT. $ext. '/extension.json');
            if ($jsonData === false) {
                continue;
            }
            $meta = json_decode($jsonData, true);
            if (!is_array($meta) || !isset($meta['bundleId']) || !isset($meta['name']) || !isset($meta['description'])) {
                continue;
            }
            if (!is_string($meta['bundleId']) || !is_string($meta['name']) || !is_string($meta['description'])) {
                continue;
            }
            if ( $lang->langCode() != 'en' && is_dir(MTT_EXT. $ext. '/lang') ) {
                $lf = MTT_EXT. $ext. '/lang/'. $lang->langCode(). '.json';
                if (file_exists($lf)) {
                    $jsonText = file_get_contents($lf) ?? '';
                    $json = json_decode($jsonText, true) ?? [];
                    $lt = $json['ext.'.$ext.'.name'] ?? null;
                    if ($lt !== null) {
                        $meta['name'] = $lt;
                    }
                }
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
