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
    /** @var PDO */
    protected $dbh;

    /** @var int */
    protected $affected = 0;

    function __construct()
    {
    }

    function connect(array $params): void
    {
        $filename = $params['filename'];
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        $this->dbh = new PDO("sqlite:$filename", null, null, $options); //throws PDOException
        $this->dbh->sqliteCreateFunction('utf8_lower', [$this, 'utf8lower'], 1);
        $this->dbh->sqliteCreateCollation('UTF8CI', [$this, 'utf8ci']);
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
        return 'utf8_lower("'. $column. '") LIKE '. $this->quoteForLike($format, mb_strtolower($string, 'UTF-8'));
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

    public function utf8lower($value)
    {
        if (is_null($value)) return '';
        return mb_strtolower((string)$value, 'UTF-8');
    }

    public function utf8ci(string $str1, string $str2): int
    {
        return strcmp(mb_strtolower($str1, 'UTF-8'), mb_strtolower($str2, 'UTF-8'));
    }
}
