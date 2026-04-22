<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2021-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class Config
{
    public static bool $noDatabase = false;

    const appDomain = 'config.json';
    const userDomain = 'config.json';

    protected static ?ConfigDictionary $config;
    protected static ?ConfigDictionary $userConfig;

    public static array $appSchema = array(
        # These two parameters are used when mytinytodo index.php called not from installation directory
        # 'url' - URL where index.php is called from (ex.: http://site.com/todo.php)
        # 'mtt_url' - directory URL where mytinytodo is installed (with trailing slash) (ex.: http://site.com/lib/mytinytodo/)
        'url' => array('default'=>'', 'type'=>'s'),
        'mtt_url' => array('default'=>'', 'type'=>'s'),

        # Top title
        'title' => array('default'=>'', 'type'=>'s'),

        # Language pack
        'lang' => array('default'=>'en', 'type'=>'s'),

        # Smart Syntax enabled flag
        'smartsyntax' => array('default'=>1, 'type'=>'i'),

        # Default Time zone
        'timezone' => array('default'=>'UTC', 'type'=>'s'),

        # To disable auto adding selected tag set value to 0
        'autotag' => array('default'=>1, 'type'=>'i'),

        # duedate calendar format: 1 => y-m-d (default), 2 => m/d/y, 3 => d.m.y
        'duedateformat' => array('default'=>1, 'type'=>'i'),

        # First day of week: 0-Sunday, 1-Monday, 2-Tuesday, .. 6-Saturday
        'firstdayofweek' => array('default'=>1, 'type'=>'i', 'options'=>array(0,1,2,3,4,5,6)),

        # Date/time formats
        'clock' => array('default'=>24, 'type'=>'i', 'options'=>array(12,24)),
        'dateformat' => array('default'=>'j M Y', 'type'=>'s'),
        'dateformat2' => array('default'=>'n/j/y', 'type'=>'s'),
        'dateformatshort' => array('default'=>'j M', 'type'=>'s'),

        # Show task date in list
        'showdate' => array('default'=>0, 'type'=>'i'),
        'showtime' => array('default'=>0, 'type'=>'i'),
        'showdateInline' => array('default'=>0, 'type'=>'i'),
        'exactduedate' => array('default'=>0, 'type'=>'i'),

        # Use Markdown syntax for notes. Set to 'v1' to use old v1.6 syntax.
        'markup' => array('default'=>'markdown', 'type'=>'s'),

        # Appearance: system default or always light
        'appearance' => array('default'=>'system', 'type'=>'s', 'options'=>array('system','light','dark')),

        # New tasks counter
        'newTaskCounter'     => array('default' => 0, 'type'=>'i'),
        'newTaskCounterIcon' => array('default' => 0, 'type'=>'i'),

        # Array of activated extensions
        'extensions' => array('default'=>[], 'type'=>'a')
    );

    public static array $userSchema = array(
        # Language
        'lang' => array('default'=>'en', 'type'=>'s'),

        # Default Time zone
        'timezone' => array('default'=>'UTC', 'type'=>'s'),

        # To disable auto adding selected tag set value to 0
        'autotag' => array('default'=>1, 'type'=>'i'),

        # duedate calendar format: 1 => y-m-d (default), 2 => m/d/y, 3 => d.m.y
        'duedateformat' => array('default'=>1, 'type'=>'i'),

        # First day of week: 0-Sunday, 1-Monday, 2-Tuesday, .. 6-Saturday
        'firstdayofweek' => array('default'=>1, 'type'=>'i', 'options'=>array(0,1,2,3,4,5,6)),

        # Date/time formats
        'clock' => array('default'=>24, 'type'=>'i', 'options'=>array(12,24)),
        'dateformat' => array('default'=>'j M Y', 'type'=>'s'),
        'dateformat2' => array('default'=>'n/j/y', 'type'=>'s'),
        'dateformatshort' => array('default'=>'j M', 'type'=>'s'),

        # Show task date in list
        'showdate' => array('default'=>0, 'type'=>'i'),
        'showtime' => array('default'=>0, 'type'=>'i'),
        'showdateInline' => array('default'=>0, 'type'=>'i'),
        'exactduedate' => array('default'=>0, 'type'=>'i'),

        # Appearance: system default or always light
        'appearance' => array('default'=>'system', 'type'=>'s', 'options'=>array('system','light','dark')),

        # New tasks counter
        'newTaskCounter'     => array('default' => 0, 'type'=>'i'),
        'newTaskCounterIcon' => array('default' => 0, 'type'=>'i'),
    );

    public static function load(): void
    {
        if (self::$noDatabase) {
            return;
        }
        $j = AppConfig::requestDomain(static::appDomain);
        if (isset($j['password']))
            unset($j['password']);
        static::$config = ConfigDictionary::dictionary($j, static::$appSchema);
    }


    public static function loadUserConfig(): void
    {
        if (self::$noDatabase) {
            return;
        }
        $userId = userId();
        if (!$userId)
            return;

        $j = UserConfig::requestUserDomain($userId, static::userDomain);
        if (is_null($j))
            return;
        $dict = ConfigDictionary::dictionary($j, Config::$userSchema);
        # validate signature?
        static::$userConfig = $dict;
    }

    public static function getConfig(): ?ConfigDictionary
    {
        return static::$config;
    }

    public static function get(string $key)
    {
        if (isset(static::$userConfig)) {
            $v = static::$userConfig->get($key);
            if (!is_null($v))
                return $v;
        }
        return static::$config->get($key);
    }


    public static function getUrl(string $key)
    {
        if (isset(static::$userConfig)) {
            $v = static::$userConfig->getUrl($key);
            if (! is_null($v))
                return $v;
        }
        static::$config->getUrl($key);
    }

    public static function getList(string $key): ?array
    {
        $a = static::get($key);
        if (is_null($a)) {
            return null;
        }
        else if (!is_array($a)) {
            error_log("Unexpected type in Config for key '$key' (array expected)");
            return null;
        }
        else if (!array_is_list($a)) {
            return array_values($a);
        }
        return $a;
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public static function set(string $key, $value)
    {
        if (self::isValidConfigParam($key, $value))
            self::$config[$key] = $value;
    }


    /**
     *
     * @return void
     * @throws Exception
     */
    public static function save()
    {
        $j = array();
        foreach (self::$params as $param => $v)
        {
            if ( !isset(self::$config[$param]) ) $val = $v['default'];
            elseif ( isset($v['options']) && !in_array(self::$config[$param], $v['options'])) $val = $v['default'];
            else $val = self::$config[$param];

            if ($v['type'] == 'i') {
                $val = (int)$val;
            }
            else if ($v['type'] == 'a') {
                if (!is_array($val)) $val = [];
            }
            else {
                $val = strval($val);
            }

            $j[$param] = $val;
        }
        self::saveDomain('config.json', $j);
    }

    /**
     *
     * @param string $key
     * @return array
     * @throws Exception
     */
    public static function requestDomain(string $key): array
    {
        $db = DBConnection::instance();
        $json = $db->sq("SELECT param_value FROM {$db->prefix}settings WHERE param_key = ?", array($key));
        if (!$json)
            return array();
        $j = json_decode($json, true, 100, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($j === null) {
            error_log("MTT Error: Failed to decode JSON object with settings. Code: ". (int)json_last_error());
            return array();
        }
        return $j;
    }


    /**
     *
     * @return array
     * @throws Exception
     */
    public static function requestDefaultDomain(): array
    {
        return self::requestDomain('config.json');
    }


    /**
     *
     * @param string $key
     * @param array $array
     * @return void
     * @throws Exception
     */
    public static function saveDomain(string $key, array $array)
    {
        $json = json_encode($array, JSON_PRETTY_PRINT /*| JSON_INVALID_UTF8_SUBSTITUTE*/);
        if ($json === false) {
            throw new Exception("Failed to create JSON object with settings. Code: ". (int)json_last_error());
        }
        $db = DBConnection::instance();
        $keyExists = $db->sq("SELECT COUNT(param_key) FROM {$db->prefix}settings WHERE param_key = ?", array($key) );
        if ($keyExists) {
            $db->ex("UPDATE {$db->prefix}settings SET param_value = ? WHERE param_key = ?", array($json,$key) );
        }
        else {
            $db->ex("INSERT INTO {$db->prefix}settings (param_key,param_value) VALUES (?,?)", array($key,$json) );
        }
    }

}



class SetupDbConfig
{
    /** @var array[] */
    private static $dbparams = array(
        # Database type: sqlite or mysql or postgres
        'db.type'      => array('default'=>'sqlite', 'type'=>'s'),

        # Specific database api
        'db.driver'    => array('default'=>'', 'type'=>'s'),

        # Mysql/Postgres connection settings
        'db.host'     => array('default'=>'localhost',  'type'=>'s'),
        'db.user'     => array('default'=>'mtt',        'type'=>'s'),
        'db.password' => array('default'=>'mtt',        'type'=>'s'),
        'db.name'     => array('default'=>'mytinytodo', 'type'=>'s'),

        # Prefix for table names
        'db.prefix'   => array('default'=>'', 'type'=>'s')
    );

    /** @var mixed[] */
    private static $config = array();

    public static function get($key)
    {
        if (isset(self::$config[$key])) return self::$config[$key];
        elseif (isset(self::$dbparams[$key])) return self::$dbparams[$key]['default'];
        else return null;
    }

    public static function set($key, $value)
    {
        if ($key == "db.prefix" && $value != "" && !preg_match("/^[a-zA-Z0-9_]+$/", $value)) {
            throw new Exception("Incorrect table prefix. Can contain only latin letters, digits and underscore character.");
        }
        self::$config[$key] = $value;
    }


    public static function defineDbConstants()
    {
        define("MTT_DB_TYPE", self::get('db.type'));
        define("MTT_DB_HOST", self::get('db.host'));
        define("MTT_DB_USER", self::get('db.user'));
        define("MTT_DB_PASSWORD", self::get('db.password'));
        define("MTT_DB_NAME", self::get('db.name'));
        define("MTT_DB_PREFIX", self::get('db.prefix'));
        if ( self::get('db.driver') != '' ) {
            define("MTT_DB_DRIVER", self::get('db.driver'));
        }
    }

    public static function dbConfigAsFileContents(): string
    {
        $a = array();
        $a[] = "<?php\n";
        $a[] = "// myTinyTodo Database connection configuration\n";
        $a[] = self::prepareDbDefine("MTT_DB_TYPE", self::get('db.type'));
        $a[] = self::prepareDbDefine("MTT_DB_HOST", self::get('db.host'));
        $a[] = self::prepareDbDefine("MTT_DB_USER", self::get('db.user'));
        $a[] = self::prepareDbDefine("MTT_DB_PASSWORD", self::get('db.password'));
        $a[] = self::prepareDbDefine("MTT_DB_NAME", self::get('db.name'));
        $a[] = self::prepareDbDefine("MTT_DB_PREFIX", self::get('db.prefix'));
        $a[] = self::prepareDbDefine("MTT_DB_DRIVER", self::get('db.driver'));
        $salt = defined('MTT_SALT') ? MTT_SALT : randomString2(64);
        $a[] = self::prepareDbDefine("MTT_SALT", $salt) . "\n";
        return implode("\n", $a);
    }

    private static function prepareDbDefine(string $key, string $value): string
    {
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $key)) {
            throw new Exception("Unexpected constant name: ". $key);
        }
        if (preg_match('~\R~', $value)) { # newlines
            throw new Exception("Unexpected constant value: ". $value);
        }
        $value = addslashes($value);
        return "define('$key', '$value');";
    }

    public static function saveDbConfig()
    {
        $contents = self::dbConfigAsFileContents();
        $f = fopen(MTTPATH. 'config.php', 'w');
        if ($f === false)
            throw new Exception("Error while saving config file");
        fwrite($f, $contents);
        fclose($f);

        //Reset Zend OPcache
        //opcache_get_status() sometimes crashes
        if (function_exists("opcache_invalidate") && 0 != (int)opcache_get_configuration()["directives"]["opcache.enable"]) {
            opcache_invalidate(MTTPATH. 'config.php', true);
        }
    }
}



class AppConfig
{
    public static function requestDictionary(string $key, ?array $schema = null): ?ConfigDictionary
    {
        $j = static::requestDomain($key) ?? [];
        return ConfigDictionary::dictionary($j, $schema);
    }

    /**
     *
     * @param string $key
     * @return array
     * @throws Exception
     */
    public static function requestDomain(string $key): array
    {
        $db = DBConnection::instance();
        $json = $db->sq("SELECT param_value FROM {$db->prefix}settings WHERE param_key = ?", array($key));
        if (!$json)
            return array();
        $j = json_decode($json, true, 100, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($j === null) {
            error_log("MTT Error: Failed to decode JSON object with settings. Code: ". (int)json_last_error());
            return array();
        }
        return $j;
    }

    /**
     *
     * @param string $key
     * @param array $array
     * @return void
     * @throws Exception
     */
    public static function saveDomain(string $key, array $array)
    {
        $json = json_encode($array, JSON_PRETTY_PRINT /*| JSON_INVALID_UTF8_SUBSTITUTE*/);
        if ($json === false) {
            throw new Exception("Failed to create JSON object with settings. Code: ". (int)json_last_error());
        }
        $db = DBConnection::instance();
        $keyExists = $db->sq("SELECT COUNT(param_key) FROM {$db->prefix}settings WHERE param_key = ?", array($key) );
        if ($keyExists) {
            $db->ex("UPDATE {$db->prefix}settings SET param_value = ? WHERE param_key = ?", array($json,$key) );
        }
        else {
            $db->ex("INSERT INTO {$db->prefix}settings (param_key,param_value) VALUES (?,?)", array($key,$json) );
        }
    }

    public static function saveDictionary(string $domain, ConfigDictionary $dict)
    {
        static::saveDomain($domain, $dict->asArray());
    }
}



class UserConfig
{
    public static function requestDomain(string $key): ?array
    {
        $userId = userId();
        return static::requestUserDomain($userId, $key);
    }

    public static function requestUserDomain(int $userId, string $key): ?array
    {
        $db = DBConnection::instance();
        $json = $db->sq("SELECT param_value FROM {$db->prefix}usersettings WHERE user_id = ? AND param_key = ?",  [$userId, $key]);
        if (!$json)
            return null;
        $j = json_decode($json, true, 100, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($j === null) {
            error_log("MTT Error: Failed to decode JSON object with settings. Code: ". (int)json_last_error());
            return null;
        }
        return $j;
    }

    public static function saveDomain(string $key, array $array)
    {
        $userId = userId();
        return static::saveUserDomain($userId, $key, $array);
    }

    public static function saveUserDomain(int $userId, string $key, array $array)
    {
        $json = json_encode($array, JSON_PRETTY_PRINT /*| JSON_INVALID_UTF8_SUBSTITUTE*/);
        if ($json === false) {
            throw new Exception("Failed to create JSON object with settings. Code: ". (int)json_last_error());
        }
        $db = DBConnection::instance();
        $keyExists = $db->sq("SELECT COUNT(param_key) FROM {$db->prefix}usersettings WHERE user_id = ? AND param_key = ?", [$userId, $key] );
        if ($keyExists) {
            $db->ex("UPDATE {$db->prefix}usersettings SET param_value = ? WHERE user_id = ? AND param_key = ?", [$json, $userId, $key] );
        }
        else {
            $db->ex("INSERT INTO {$db->prefix}usersettings (user_id,param_key,param_value) VALUES (?,?,?)", [$userId, $key, $json] );
        }
    }

    public static function saveDictionary(string $key, ConfigDictionary $dict)
    {
        static::saveDomain($key, $dict->asArray());
    }

}



class ConfigDictionary
{
    protected array $config;
    protected ?array $schema;

    function __construct(array $schema = null)
    {
        $this->schema = $schema;
    }

    public static function dictionary(array $array, array $schema = null): ConfigDictionary
    {
        $dict = new static($schema);
        $dict->setValues($array);
        return $dict;
    }

    public function setValues(array $array)
    {
        foreach ($array as $key => $val) {
            if ($this->isValidConfigParam($key, $val)) {
                $this->config[$key] = $val;
            }
        }
    }

    protected function isValidConfigParam(string $key, $value): bool
    {
        # Ignore user defined params
        if (!isset($this->schema[$key])) {
            return true;
        }

        # Check type
        switch ($this->schema[$key]['type']) {
            case 's': if (!is_string($value)) return false; break;
            case 'i': if (!is_int($value)) return false; break;
            case 'a': if (!is_array($value)) return false; break;
        }

        # values
        $options = $this->schema[$key]['options'] ?? false;
        if ($options && !in_array($value, $options))
            return false;

        return true;
    }

    public function set(string $key, $value): void
    {
        if ($this->isValidConfigParam($key, $value))
            $this->config[$key] = $value;
    }

    public function get(string $key)
    {
        if (isset($this->config[$key]))
            return $this->config[$key];
        elseif (isset($this->schema) && isset($this->schema[$key]))
            return $this->schema[$key]['default'];
        else
            return null;
    }

    public function getUrl(string $key)
    {
        $url = '';
        if ( isset($this->config[$key]) )
            $url = $this->config[$key];
        else if ( isset($this->schema) && isset($this->schema[$key]) )
            $url = $this->schema[$key]['default'];
        else return null;
        return str_replace( ["\r","\n"], '', $url );
    }

    public function getList(string $key): ?array
    {
        $a = $this->get($key);
        if (is_null($a)) {
            return null;
        }
        else if (!is_array($a)) {
            error_log("Unexpected type in ConfigDictionary for key '$key' (array expected)");
            return null;
        }
        else if (!array_is_list($a)) {
            return array_values($a);
        }
        return $a;
    }

    public function asArray(bool $strictSchema = true): array
    {
        if (!$this->schema || !$strictSchema)
            return $this->config;

        $j = array();
        foreach ($this->schema as $param => $v)
        {
            if ( !isset($this->config[$param]) ) $val = $v['default'];
            elseif ( isset($v['options']) && !in_array($this->config[$param], $v['options'])) $val = $v['default'];
            else $val = $this->config[$param];

            if ($v['type'] == 'i') {
                $val = (int)$val;
            }
            else if ($v['type'] == 'a') {
                if (!is_array($val)) $val = [];
            }
            else {
                $val = strval($val);
            }

            $j[$param] = $val;
        }
        return $j;
    }
}
