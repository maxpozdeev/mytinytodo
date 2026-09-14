<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2021-2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class DBConnection
{
    const DBTYPE_SQLITE = "sqlite";
    const DBTYPE_MYSQL = "mysql";
    const DBTYPE_POSTGRES = "postgres";

    protected static AbstractDatabase $instance;

    public static function init(AbstractDatabase $instance) : AbstractDatabase
    {
        self::$instance = $instance;
        return $instance;
    }

    public static function instance() : AbstractDatabase
    {
        if (!isset(self::$instance)) {
            throw new Exception("DBConnection is not initialized");
        }
        return self::$instance;
    }

    public static function setTablePrefix(string $prefix)
    {
        $db = self::instance();
        $db->setPrefix($prefix);
    }
}

/**
 *
 * @property-read string $prefix
 * @property-read string $lastQuery
 * @property-read string $orderCollation
 * @property-read string $equalCollation
 */
abstract class AbstractDatabase
{
    const DBTYPE = '';
    protected static array $readonlyProps = ['prefix', 'lastQuery', 'orderCollation', 'equalCollation'];

    protected string $prefix = '';
    protected string $lastQuery = '';
    protected string $orderCollation = '';
    protected string $equalCollation = '';

    protected ?string $logQueryToFile = null;
    protected bool $isFirstLog = true;
    protected int $lastQueryStart = 0;
    protected int $lastQueryFinish = 0;

    abstract function connect(array $params): void;
    abstract function sq(string $query, ?array $values = null);
    abstract function sqa(string $query, ?array $values = null): ?array;
    abstract function dq(string $query, ?array $values = null): AbstractDatabaseResult;
    abstract function ex(string $query, ?array $values = null): void;
    abstract function affected(): int;
    abstract function quote($value): string;
    abstract function quoteForLike(string $format, string $string): string;
    abstract function like(string $column, string $format, string $string): string;
    abstract function ciEquals(string $column, string $value): string;
    abstract function lastInsertId(?string $name = null): ?string;
    abstract function tableExists(string $table): bool;
    abstract function tableFieldExists(string $table, string $field): bool;

    function __get(string $propName) {
        if ( in_array($propName, static::$readonlyProps) ) {
            return $this->{$propName};
        }
        throw new Error("Attempt to read undefined property ". get_class($this). "::\$$propName");
    }

    function setPrefix(string $prefix): void {
        if ($prefix != '' && !preg_match("/^[a-zA-Z0-9_]+$/", $prefix)) {
            throw new Exception("Incorrect table prefix");
        }
        $this->prefix = $prefix;
    }

    /**
     * Set a filename to write every sql query before execution.
     * Set to null to disable logging.
     * Return false if given filename is not writable or creatable.
     * @param string $path
     * @return bool
     */
    function setLogQueryToFile(?string $path): bool
    {
        if (is_null($path)) {
        }
        else if (file_exists($path)) {
            if (!is_writable($path)) {
                return false;
            }
        }
        else if (!is_writable(dirname($path))) {
            return false;
        }
        $this->logQueryToFile = $path;
        $this->isFirstLog = true;
        return true;
    }

    function setLastQuery(string $lastQuery) {
        $this->lastQueryStart = hrtime(true);
        $this->lastQuery = $lastQuery;
    }

    function setLastQueryFinished(bool $failed = false) {
        $this->lastQueryFinish = $failed ? 0 : hrtime(true);
        $this->logLastQuery();
    }

    function logLastQuery()
    {
        if (MTT_DEBUG && $this->logQueryToFile !== null)
        {
            $f = fopen($this->logQueryToFile, "a");
            if ($f) {
                if ($this->isFirstLog) {
                    $this->isFirstLog = false;
                    $dt = (new DateTime())->format('Y-m-d H:i:s.v');
                    fwrite($f, "====== $dt (". static::DBTYPE. ") ======\n");
                }
                # execution time of last query
                if ($this->lastQueryFinish)
                    $time = "+". number_format( ($this->lastQueryFinish - $this->lastQueryStart) / 1_000_000_000, 3, '.', ''). " ";
                else
                    $time = 'Error ';

                fwrite($f, "{$time}{$this->lastQuery}\n");
                fclose($f);
            }
        }
    }
}

abstract class AbstractDatabaseResult
{
    abstract function fetchRow(): ?array;
    abstract function fetchAssoc(): ?array;
}

