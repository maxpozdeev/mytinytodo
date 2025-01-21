<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009,2019-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class DatabaseResult_Sqlite3 extends DatabaseResult_Abstract
{
    /** @var PDOStatement */
    protected $q;

    /** @var int */
    protected $affected;

    function __construct(PDO $dbh, string $query, bool $resultless = false)
    {
        // use with DELETE, INSERT, UPDATE
        if ($resultless)
        {
            $this->affected = (int) $dbh->exec($query); //throws PDOException
        }
        // SELECT
        else
        {
            $this->q = $dbh->query($query); //throws PDOException
            $this->affected = $this->q->rowCount();
        }
    }

    function fetchRow(): ?array
    {
        $res = $this->q->fetch(PDO::FETCH_NUM);
        if ($res === false || !is_array($res)) {
            return null;
        }
        return $res;
    }

    function fetchAssoc(): ?array
    {
        $res = $this->q->fetch(PDO::FETCH_ASSOC);
        if ($res === false || !is_array($res)) {
            return null;
        }
        return $res;
    }

    function rowsAffected(): int
    {
        return $this->affected;
    }

}

class Database_Sqlite3 extends Database_Abstract
{
    const DBTYPE = 'sqlite';

    /** @var PDO */
    protected $dbh;

    /** @var int */
    protected $affected = 0;

    /** @var bool */
    protected $useNormalizedUtf8 = true;

    function __construct(array $params = null)
    {
        if (is_array($params)) {
            if (isset($params['useNormalizedUtf8'])) {
                $this->useNormalizedUtf8 = boolval($params['useNormalizedUtf8']);
            }
        }
    }

    function connect(array $params): void
    {
        $filename = $params['filename'];
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        $this->dbh = new PDO("sqlite:$filename", null, null, $options); //throws PDOException
        $this->dbh->sqliteCreateFunction('utf8_lower', [$this, 'utf8_lower'], 1);
        $this->dbh->sqliteCreateFunction('utf8_normalized_lower', [$this, 'utf8_normalized_lower'], 1);
        $this->dbh->sqliteCreateCollation('UTF8CI', [$this, 'collate_utf8ci']);
        $this->dbh->sqliteCreateCollation('UTF8CI_NORMALIZED', [$this, 'collate_utf8ci_normalized']);
    }

    /*
        SELECT queries for single row
    */
    function sq(string $query, ?array $values = null)
    {
        $q = $this->_dq($query, $values);

        $res = $q->fetchRow();
        if ($res === false || !is_array($res)) {
            return null;
        }

        if (sizeof($res) > 1) return $res;
        else return $res[0];
    }

    /*
        Returns single row of SELECT query as dictionary array (FETCH_ASSOC).
    */
    function sqa(string $query, ?array $values = null): ?array
    {
        $q = $this->_dq($query, $values);
        $res = $q->fetchAssoc();
        if ($res === false || !is_array($res)) {
            return null;
        }
        return $res;
    }

    /*
        SELECT queries for multiple rows
    */
    function dq(string $query, ?array $values = null) : DatabaseResult_Abstract
    {
        return $this->_dq($query, $values);
    }

    /*
        for resultless queries like INSERT,UPDATE,DELETE
    */
    function ex(string $query, ?array $values = null): void
    {
        $this->_dq($query, $values, true);
    }

    private function _dq(string $query, ?array $values = null, bool $resultless = false) : DatabaseResult_Abstract
    {
        if (null !== $values && sizeof($values) > 0)
        {
            $m = explode('?', $query);
            if (sizeof($m) < sizeof($values)+1) {
                throw new Exception("params to set MORE than query params");
            }
            if (sizeof($m) > sizeof($values)+1) {
                throw new Exception("params to set LESS than query params");
            }
            $query = "";
            for ($i=0; $i<sizeof($m)-1; $i++) {
                $query .= $m[$i]. $this->quote($values[$i]);
            }
            $query .= $m[$i];
        }
        $this->lastQuery = $query;
        $dbr = new DatabaseResult_Sqlite3($this->dbh, $query, $resultless);
        $this->affected = $dbr->rowsAffected();
        return $dbr;
    }

    function affected(): int
    {
        return $this->affected;
    }

    function quote($value): string
    {
        if (null === $value) {
            return 'null';
        }
        return $this->dbh->quote( (string) $value);
    }

    function quoteForLike(string $format, string $string): string
    {
        $string = str_replace(array('\\','%','_'), array('\\\\','\%','\_'), $string);
        return $this->dbh->quote(sprintf($format, $string)). " ESCAPE '\'";
    }

    /**
     * Produce case-insensitive like
     */
    function like(string $column, string $format, string $string): string
    {
        $column = str_replace('"', '""', $column);
        if ($this->useNormalizedUtf8) {
            return 'utf8_normalized_lower("'. $column. '") LIKE '. $this->quoteForLike($format, $this->utf8_normalized_lower($string));
        }
        return 'utf8_lower("'. $column. '") LIKE '. $this->quoteForLike($format, $this->utf8_lower($string));
    }

    function ciEquals(string $column, string $value): string
    {
        $column = str_replace('"', '""', $column);
        if ($this->useNormalizedUtf8) {
            return 'utf8_normalized_lower("'. $column. '") = '. $this->quote($this->utf8_normalized_lower($value));
        }
        return 'utf8_lower("'. $column. '") = '. $this->quote($this->utf8_lower($value));
    }

    function lastInsertId(?string $name = null): ?string
    {
        $ret = $this->dbh->lastInsertId();
        if (false === $ret) {
            return null;
        }
        return (string) $ret;
    }

    function tableExists(string $table): bool
    {
        $exists = $this->sq("SELECT 1 FROM sqlite_master WHERE type='table' AND name=?", [$table]);
        if ($exists == "1") {
          return true;
        }
        $exists = $this->sq("SELECT 1 FROM sqlite_temp_master WHERE type='table' AND name=?", [$table]);
        if ($exists == "1") {
          return true;
        }
        return false;
    }

    function tableFieldExists(string $table, string $field): bool
    {
        $q = $this->dq("PRAGMA table_info(". $this->quote($table). ")");
        while ($r = $q->fetchRow()) {
            if ($r[1] == $field) return true;
        }
        return false;
    }

    public function utf8_lower($value): string
    {
        if (is_null($value)) return '';
        return mb_strtolower((string)$value, 'UTF-8');
    }

    public function utf8_normalized_lower($value): string
    {
        if (is_null($value)) return '';
        $value = self::normalizeValue((string) $value);
        return mb_strtolower($value, 'UTF-8');
    }

    public function collate_utf8ci(string $str1, string $str2): int
    {
        return strcmp(mb_strtolower($str1, 'UTF-8'), mb_strtolower($str2, 'UTF-8'));
    }

    public function collate_utf8ci_normalized(string $str1, string $str2): int
    {
        $str1 = self::normalizeValue($str1);
        $str2 = self::normalizeValue($str2);
        return strcmp(mb_strtolower($str1, 'UTF-8'), mb_strtolower($str2, 'UTF-8'));
    }

    public static function normalizeValue(string $str): string
    {
        $str = Normalizer::normalize($str, Normalizer::FORM_KD);

        if (false === preg_match_all("/./u", $str, $m)) {
            $ea = error_get_last();
            $error = ($ea && isset($ea['message'])) ? $ea['message'] : "preg_match_all() failed";
            throw new Exception($error);
        }
        $chars = $m[0];
        static $map = [
            // https://en.wikipedia.org/wiki/Combining_character
            "\u{0300}" => '', "\u{0301}" => '', "\u{0302}" => '', "\u{0303}" => '', "\u{0304}" => '', "\u{0305}" => '', "\u{0306}" => '', "\u{0307}" => '',
            "\u{0308}" => '', "\u{0309}" => '', "\u{030a}" => '', "\u{030b}" => '', "\u{030c}" => '', "\u{030d}" => '', "\u{030e}" => '', "\u{030f}" => '',
            "\u{0310}" => '', "\u{0311}" => '', "\u{0312}" => '', "\u{0313}" => '', "\u{0314}" => '', "\u{0315}" => '', "\u{0316}" => '', "\u{0317}" => '',
            "\u{0318}" => '', "\u{0319}" => '', "\u{031a}" => '', "\u{031b}" => '', "\u{031c}" => '', "\u{031d}" => '', "\u{031e}" => '', "\u{031f}" => '',
            "\u{0320}" => '', "\u{0321}" => '', "\u{0322}" => '', "\u{0323}" => '', "\u{0324}" => '', "\u{0325}" => '', "\u{0326}" => '', "\u{0327}" => '',
            "\u{0328}" => '', "\u{0329}" => '', "\u{032a}" => '', "\u{032b}" => '', "\u{032c}" => '', "\u{032d}" => '', "\u{032e}" => '', "\u{032f}" => '',
            "\u{0330}" => '', "\u{0331}" => '', "\u{0332}" => '', "\u{0333}" => '', "\u{0334}" => '', "\u{0335}" => '', "\u{0336}" => '', "\u{0337}" => '',
            "\u{0338}" => '', "\u{0339}" => '', "\u{033a}" => '', "\u{033b}" => '', "\u{033c}" => '', "\u{033d}" => '', "\u{033e}" => '', "\u{033f}" => '',
            "\u{0340}" => '', "\u{0341}" => '', "\u{0342}" => '', "\u{0343}" => '', "\u{0344}" => '', "\u{0345}" => '', "\u{0346}" => '', "\u{0347}" => '',
            "\u{0348}" => '', "\u{0349}" => '', "\u{034a}" => '', "\u{034b}" => '', "\u{034c}" => '', "\u{034d}" => '', "\u{034e}" => '', "\u{034f}" => '',
            "\u{0350}" => '', "\u{0351}" => '', "\u{0352}" => '', "\u{0353}" => '', "\u{0354}" => '', "\u{0355}" => '', "\u{0356}" => '', "\u{0357}" => '',
            "\u{0358}" => '', "\u{0359}" => '', "\u{035a}" => '', "\u{035b}" => '', "\u{035c}" => '', "\u{035d}" => '', "\u{035e}" => '', "\u{035f}" => '',
            "\u{0360}" => '', "\u{0361}" => '', "\u{0362}" => '', "\u{0363}" => '', "\u{0364}" => '', "\u{0365}" => '', "\u{0366}" => '', "\u{0367}" => '',
            "\u{0368}" => '', "\u{0369}" => '', "\u{036a}" => '', "\u{036b}" => '', "\u{036c}" => '', "\u{036d}" => '', "\u{036e}" => '', "\u{036f}" => '',

            "\u{1ab0}" => '', "\u{1ab1}" => '', "\u{1ab2}" => '', "\u{1ab3}" => '', "\u{1ab4}" => '', "\u{1ab5}" => '', "\u{1ab6}" => '', "\u{1ab7}" => '',
            "\u{1ab8}" => '', "\u{1ab9}" => '', "\u{1aba}" => '', "\u{1abb}" => '', "\u{1abc}" => '', "\u{1abd}" => '', "\u{1abe}" => '', "\u{1abf}" => '',
            "\u{1ac0}" => '', "\u{1ac1}" => '', "\u{1ac2}" => '', "\u{1ac3}" => '', "\u{1ac4}" => '', "\u{1ac5}" => '', "\u{1ac6}" => '', "\u{1ac7}" => '',
            "\u{1ac8}" => '', "\u{1ac9}" => '', "\u{1aca}" => '', "\u{1acb}" => '', "\u{1acc}" => '', "\u{1acd}" => '', "\u{1ace}" => '', "\u{1acf}" => '',
            "\u{1ad0}" => '', "\u{1ad1}" => '', "\u{1ad2}" => '', "\u{1ad3}" => '', "\u{1ad4}" => '', "\u{1ad5}" => '', "\u{1ad6}" => '', "\u{1ad7}" => '',
            "\u{1ad8}" => '', "\u{1ad9}" => '', "\u{1ada}" => '', "\u{1adb}" => '', "\u{1adc}" => '', "\u{1add}" => '', "\u{1ade}" => '', "\u{1adf}" => '',
            "\u{1ae0}" => '', "\u{1ae1}" => '', "\u{1ae2}" => '', "\u{1ae3}" => '', "\u{1ae4}" => '', "\u{1ae5}" => '', "\u{1ae6}" => '', "\u{1ae7}" => '',
            "\u{1ae8}" => '', "\u{1ae9}" => '', "\u{1aea}" => '', "\u{1aeb}" => '', "\u{1aec}" => '', "\u{1aed}" => '', "\u{1aee}" => '', "\u{1aef}" => '',
            "\u{1af0}" => '', "\u{1af1}" => '', "\u{1af2}" => '', "\u{1af3}" => '', "\u{1af4}" => '', "\u{1af5}" => '', "\u{1af6}" => '', "\u{1af7}" => '',
            "\u{1af8}" => '', "\u{1af9}" => '', "\u{1afa}" => '', "\u{1afb}" => '', "\u{1afc}" => '', "\u{1afd}" => '', "\u{1afe}" => '', "\u{1aff}" => '',

            "\u{1dc0}" => '', "\u{1dc1}" => '', "\u{1dc2}" => '', "\u{1dc3}" => '', "\u{1dc4}" => '', "\u{1dc5}" => '', "\u{1dc6}" => '', "\u{1dc7}" => '',
            "\u{1dc8}" => '', "\u{1dc9}" => '', "\u{1dca}" => '', "\u{1dcb}" => '', "\u{1dcc}" => '', "\u{1dcd}" => '', "\u{1dce}" => '', "\u{1dcf}" => '',
            "\u{1dd0}" => '', "\u{1dd1}" => '', "\u{1dd2}" => '', "\u{1dd3}" => '', "\u{1dd4}" => '', "\u{1dd5}" => '', "\u{1dd6}" => '', "\u{1dd7}" => '',
            "\u{1dd8}" => '', "\u{1dd9}" => '', "\u{1dda}" => '', "\u{1ddb}" => '', "\u{1ddc}" => '', "\u{1ddd}" => '', "\u{1dde}" => '', "\u{1ddf}" => '',
            "\u{1de0}" => '', "\u{1de1}" => '', "\u{1de2}" => '', "\u{1de3}" => '', "\u{1de4}" => '', "\u{1de5}" => '', "\u{1de6}" => '', "\u{1de7}" => '',
            "\u{1de8}" => '', "\u{1de9}" => '', "\u{1dea}" => '', "\u{1deb}" => '', "\u{1dec}" => '', "\u{1ded}" => '', "\u{1dee}" => '', "\u{1def}" => '',
            "\u{1df0}" => '', "\u{1df1}" => '', "\u{1df2}" => '', "\u{1df3}" => '', "\u{1df4}" => '', "\u{1df5}" => '', "\u{1df6}" => '', "\u{1df7}" => '',
            "\u{1df8}" => '', "\u{1df9}" => '', "\u{1dfa}" => '', "\u{1dfb}" => '', "\u{1dfc}" => '', "\u{1dfd}" => '', "\u{1dfe}" => '', "\u{1dff}" => '',

            "\u{20d0}" => '', "\u{20d1}" => '', "\u{20d2}" => '', "\u{20d3}" => '', "\u{20d4}" => '', "\u{20d5}" => '', "\u{20d6}" => '', "\u{20d7}" => '',
            "\u{20d8}" => '', "\u{20d9}" => '', "\u{20da}" => '', "\u{20db}" => '', "\u{20dc}" => '', "\u{20dd}" => '', "\u{20de}" => '', "\u{20df}" => '',
            "\u{20e0}" => '', "\u{20e1}" => '', "\u{20e2}" => '', "\u{20e3}" => '', "\u{20e4}" => '', "\u{20e5}" => '', "\u{20e6}" => '', "\u{20e7}" => '',
            "\u{20e8}" => '', "\u{20e9}" => '', "\u{20ea}" => '', "\u{20eb}" => '', "\u{20ec}" => '', "\u{20ed}" => '', "\u{20ee}" => '', "\u{20ef}" => '',
            "\u{20f0}" => '', "\u{20f1}" => '', "\u{20f2}" => '', "\u{20f3}" => '', "\u{20f4}" => '', "\u{20f5}" => '', "\u{20f6}" => '', "\u{20f7}" => '',
            "\u{20f8}" => '', "\u{20f9}" => '', "\u{20fa}" => '', "\u{20fb}" => '', "\u{20fc}" => '', "\u{20fd}" => '', "\u{20fe}" => '', "\u{20ff}" => '',

            "\u{fe20}" => '', "\u{fe21}" => '', "\u{fe22}" => '', "\u{fe23}" => '', "\u{fe24}" => '', "\u{fe25}" => '', "\u{fe26}" => '', "\u{fe27}" => '',
            "\u{fe28}" => '', "\u{fe29}" => '', "\u{fe2a}" => '', "\u{fe2b}" => '', "\u{fe2c}" => '', "\u{fe2d}" => '', "\u{fe2e}" => '', "\u{fe2f}" => '',

            'Æ' => 'AE',  // "U+00c6"
            'æ' => 'ae',  // "U+00e6"
            'Œ' => 'OE',  // "U+0152"
            'œ' => 'oe',  // "U+0153"
            'Ł' => 'L', 'ł' => 'L'  //U+141 and U+142
        ];

        $len = count($chars);
        for ($i = 0; $i < $len; $i++) {
            $unichar = $chars[$i];
            if (isset($map[$unichar])) {
                $chars[$i] = $map[$unichar];
            }
        }
        return implode('', $chars);
    }

}
