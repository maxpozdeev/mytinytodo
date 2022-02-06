<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2020-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

/*
    myTinyTodo language class
*/

class Lang
{
    protected static $instance;
    protected static $langDir = MTTLANG;
    protected $code = 'en';
    protected $default = 'en';
    protected $strings;

    public static function instance()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    public static function loadLangOrDie($code, $die = 1)
    {
        $lang = self::instance();

        //check if json file exists
        if ( self::langExists($code) ) {
            $jsonString = file_get_contents( self::$langDir. "{$code}.json" );
            $lang->loadJsonString($code, $jsonString);
        }
        else if ( $die == 0 ) {
            //notice?
            $lang->code = $lang->default; //sure?
            $lang->loadDefaultStrings();
        }
        else if ( $die == 1 ) {
            die("Language file not found (". htmlspecialchars($code). ".json)");
        }
    }

    public static function loadLang($code)
    {
        self::loadLangOrDie($code, 0);
    }

    public static function langExists($code)
    {
        return file_exists(self::$langDir. $code. '.json');
    }

    function loadJsonString($code, $jsonString)
    {
        $this->code = $code;
        $json = json_decode($jsonString, true);

        //load default language
        if ( $code != $this->default ) {
            $this->loadDefaultStrings();
            $this->strings = array_replace($this->strings, $json);
        }
        else {
            $this->strings = $json;
        }
    }

    function loadDefaultStrings()
    {
        if ( ! self::langExists($this->default) ) {
            die("Default language file not found (". htmlspecialchars($this->default). ".json)");
        }
        $defStr = file_get_contents($this->langDir(). "{$this->default}.json");
        $this->strings = json_decode($defStr, true);
    }

    function get($key)
    {
        if ( isset($this->strings[$key]) ) {
            return $this->strings[$key];
        }
        return $key;
    }

    function rtl()
    {
        if ( isset($this->strings['_rtl']) ) {
            return intval($this->strings['_rtl']);
        }
        return 0;
    }

    /* minimal number of translated strings to use in js front-end */
    function jsStrings()
    {
        $a = array();
        $a['daysMin'] = $this->get('days_min');
        $a['daysLong'] = $this->get('days_long');
        $a['monthsShort'] = $this->get('months_short');
        $a['monthsLong'] = $this->get('months_long');

        $this->fillWithValues($a, [
            'confirmDelete',
            'confirmLeave',
            'actionNoteSave',
            'actionNoteCancel',
            'error',
            'denied',
            'listNotFound',
            'noPublicLists',
            'invalidpass',
            'addList',
            'addListDefault',
            'renameList',
            'deleteList',
            'clearCompleted',
            'settingsSaved',
            'tags',
            'tasks',
            'f_past',
            'f_today',
            'f_soon',
            'alltasks',
            'set_header'
        ]);
        $a['_rtl'] = $this->rtl() ? 1 : 0;

        return $a;
    }

    function makeJS($pretty = 0)
    {
        $a = $this->jsStrings();
        $opts = JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $opts |= JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        }
        return json_encode($a, $opts);
    }

    protected function fillWithValues(array &$a, array $keys)
    {
        foreach ( $keys as $key ) {
            $a[$key] = $this->get($key);
        }
    }

    function langDir()
    {
        return self::$langDir;
    }

    function langCode()
    {
        return $this->code;
    }

}

