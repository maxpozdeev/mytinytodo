<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2020-2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

// ---------------------------------------------------------------------------- //
class DatabaseResult_Mysql extends DatabaseResult_Abstract
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

// ---------------------------------------------------------------------------- //
class Database_Mysql extends Database_Abstract
{
    const DBTYPE = 'mysql';

    /** @var PDO */
    protected $dbh;

    /** @var int */
    protected $affected = 0;

    protected $dbname;

    function __construct()
    {
    }

    function connect(array $params): void
    {
        $host = $params['host'];
        $user = $params['user'];
        $pass = $params['password'];
        $db = $params['db'];
        $options = array(
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        $this->dbname = $db;
        $this->dbh = new PDO("mysql:host=$host;dbname=$db", $user, $pass, $options);
    }



    /*
        Returns single row of SELECT query as indexed array (FETCH_NUM).
        Returns single field value if resulting array has only one field.
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
            for ($i=0; $i<sizeof($m)-1; $i++) {
                $query .= $m[$i]. $this->quote($values[$i]);
            }
            $query .= $m[$i];
        }
        $this->lastQuery = $query;
        $dbr = new DatabaseResult_Mysql($this->dbh, $query, $resultless);
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
