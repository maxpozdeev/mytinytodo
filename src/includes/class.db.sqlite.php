<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009,2019-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class SqliteDatabaseResult extends AbstractDatabaseResult
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

class SqliteDatabase extends AbstractDatabase
{
    const DBTYPE = 'sqlite';

    /** @var PDO|\Pdo\Sqlite */
    protected $dbh;

    /** @var int */
    protected $affected = 0;

    /** @var bool */
    protected $useNormalizedSearch = true;

    protected string $orderCollation = 'UTF8CI';
    protected string $equalCollation = 'UTF8CI';


    function __construct(?array $params = null)
    {
        if (is_array($params)) {
            if (isset($params['useNormalizedSearch'])) {
                $this->useNormalizedSearch = boolval($params['useNormalizedSearch']);
            }
            if ( $params['useNormalizedOrder'] ?? false ) {
                $this->orderCollation = 'UTF8CI_NORMALIZED';
            }
        }
    }

    function connect(array $params): void
    {
        $filename = $params['filename'];
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        if (PHP_VERSION_ID < 80500) {
            $this->dbh = new PDO("sqlite:$filename", null, null, $options); //throws PDOException
            # Deprecated since PHP 8.5
            $this->dbh->sqliteCreateFunction('utf8_lower', [$this, 'utf8_lower'], 1);
            $this->dbh->sqliteCreateFunction('utf8_normalized_lower', [$this, 'utf8_normalized_lower'], 1);
            $this->dbh->sqliteCreateCollation('UTF8CI', [$this, 'collate_utf8ci']);
            $this->dbh->sqliteCreateCollation('UTF8CI_NORMALIZED', [$this, 'collate_utf8ci_normalized']);
        }
        else {
            /** @disregard P1009 available in php 8.5 */
            $this->dbh = new \Pdo\Sqlite("sqlite:$filename", null, null, $options); //throws PDOException
            $this->dbh->createFunction('utf8_lower', [$this, 'utf8_lower'], 1);
            $this->dbh->createFunction('utf8_normalized_lower', [$this, 'utf8_normalized_lower'], 1);
            $this->dbh->createCollation('UTF8CI', [$this, 'collate_utf8ci']);
            $this->dbh->createCollation('UTF8CI_NORMALIZED', [$this, 'collate_utf8ci_normalized']);
        }
    }

    /*
        SELECT queries for single row
    */
    function sq(string $query, ?array $values = null)
    {
        $q = $this->_dq($query, $values);

        $res = $q->fetchRow();
        if ($res === null) {
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
        return $res;
    }

    /*
        SELECT queries for multiple rows
    */
    function dq(string $query, ?array $values = null) : AbstractDatabaseResult
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

    private function _dq(string $query, ?array $values = null, bool $resultless = false) : AbstractDatabaseResult
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
        $this->setLastQuery($query);
        try {
            $dbr = new SqliteDatabaseResult($this->dbh, $query, $resultless);
        }
        catch (Exception $e) {
            $this->setLastQueryFinished(true);
            throw $e;
        }
        $this->setLastQueryFinished();
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
        if ($this->useNormalizedSearch) {
            return 'utf8_normalized_lower("'. $column. '") LIKE '. $this->quoteForLike($format, $this->utf8_normalized_lower($string));
        }
        return 'utf8_lower("'. $column. '") LIKE '. $this->quoteForLike($format, $this->utf8_lower($string));
    }

    function ciEquals(string $column, string $value): string
    {
        $column = str_replace('"', '""', $column);
        if ($this->useNormalizedSearch) {
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
        $r = strcmp(mb_strtolower($str1, 'UTF-8'), mb_strtolower($str2, 'UTF-8'));
        if ($r === 0) {
            $r = strcmp($str1, $str2);
        }
        return $r;
    }

    public function collate_utf8ci_normalized(string $str1, string $str2): int
    {
        $s1 = self::normalizeValue($str1);
        $s2 = self::normalizeValue($str2);
        $r = strcmp(mb_strtolower($s1, 'UTF-8'), mb_strtolower($s2, 'UTF-8'));
        if ($r === 0) {
            $r = strcmp($s1, $s2);
        }
        if ($r === 0) {
            $r = strcmp($str1, $str2);
        }
        return $r;
    }

    public static function normalizeValue(string $str): string
    {
        $str = Normalizer::normalize($str, Normalizer::FORM_KD);

        // Remove combining diacritical marks
        //https://en.wikipedia.org/wiki/Combining_character
        $str = preg_replace('/[\x{0300}-\x{036F}\x{1AB0}-\x{1AFF}\x{1DC0}-\x{1DFF}\x{20D0}-\x{20FF}\x{FE20}-\x{FE2F}]/u', '', $str);

        //Replace special ligatures/characters
        static $specials = [
            'Æ' => 'AE',  // "U+00c6"
            'æ' => 'ae',  // "U+00e6"
            'Œ' => 'OE',  // "U+0152"
            'œ' => 'oe',  // "U+0153"
            'Ł' => 'L', 'ł' => 'L',  // U+0141 and U+0142 (Polish)
            'Ø' => 'O', 'ø' => 'o',  // U+00D8 and U+00F8 (Danish, Norwegian)
            //NB: Swedish alphabet has Å, Ä and Ö letters, sorted after Z
        ];

        $str = strtr($str, $specials);
        return $str;
    }

}
