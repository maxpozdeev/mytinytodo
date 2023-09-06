<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2019-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

// ---------------------------------------------------------------------------- //
class DatabaseResult_Mysqli extends DatabaseResult_Abstract
{
    /** @var mysqli_result */
    protected $q;

    function __construct(mysqli $dbh, string $query, bool $resultless = false)
    {
        $this->q = $dbh->query($query); //throws mysqli_sql_exception
    }

    function fetchRow(): ?array
    {
        $res = $this->q->fetch_row();
        if ($res === null || $res === false || !is_array($res)) {
            return null;
        }
        return $res;
    }

    function fetchAssoc(): ?array
    {
        $res = $this->q->fetch_assoc();
        if ($res === null || $res === false || !is_array($res)) {
            return null;
        }
        return $res;
    }
}

// ---------------------------------------------------------------------------- //
class Database_Mysqli extends Database_Abstract
{
    const DBTYPE = 'mysql';

    /** @var mysqli */
    protected $dbh;
    protected $dbname;

    function __construct()
    {
        // enable throwing exceptions
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }

    function connect(array $params): void
    {
        $host = $params['host'];
        $user = $params['user'];
        $pass = $params['password'];
        $db = $params['db'];
        $this->dbname = $db;
        $this->dbh = new mysqli($host, $user, $pass, $db); //throws mysqli_sql_exception
    }

    function lastInsertId(?string $name = null): ?string
    {
        return (string) $this->dbh->insert_id;
    }

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
        Returns single row of SELECT query as dictionary array (fetch_assoc()).
    */
    function sqa(string $query, ?array $values = null): ?array
    {
        $q = $this->_dq($query, $values);
        $res = $q->fetchAssoc();
        if ($res === false || !is_array($res)){
            return null;
        }
        return $res;
    }

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
            for ($i=0; $i < sizeof($m)-1; $i++) {
                $query .= $m[$i]. $this->quote($values[$i]);
            }
            $query .= $m[$i];
        }
        $this->lastQuery = $query;
        return new DatabaseResult_Mysqli($this->dbh, $query, $resultless);
    }

    function affected(): int
    {
        return max( (int)$this->dbh->affected_rows, 0 );
    }

    function quote($value): string
    {
        if (null === $value) {
            return 'null';
        }
        return '\''. addslashes( (string) $value). '\'';
    }

    function quoteForLike(string $format, string $string): string
    {
        $string = str_replace(array('%','_'), array('\%','\_'), addslashes($string));
        return '\''. sprintf($format, $string). '\'';
    }

    function like(string $column, string $format, string $string): string
    {
        $column = str_replace('`', '``', $column);
        return '`'. $column. '` LIKE '. $this->quoteForLike($format, $string);
    }

    function ciEquals(string $column, string $value): string
    {
        $column = str_replace('`', '``', $column);
        return 'LOWER(`'. $column. '`) = LOWER('. $this->quote($value). ')';
    }

    function tableExists(string $table): bool
    {
        $r = $this->sq("SELECT 1 FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                        array($this->dbname, $table) );
        if ($r === false || $r === null) return false;
        return true;
    }

    function tableFieldExists(string $table, string $field): bool
    {
        $table = str_replace('`', '\\`', addslashes($table));
        $q = $this->dq("DESCRIBE `$table`");
        while ($r = $q->fetchRow()) {
            if ($r[0] == $field) return true;
        }
        return false;
    }
}
