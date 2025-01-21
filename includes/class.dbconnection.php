<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2021,2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class DBConnection
{
    const DBTYPE_SQLITE = "sqlite";
    const DBTYPE_MYSQL = "mysql";
    const DBTYPE_POSTGRES = "postgres";

    protected static $instance;

    public static function init(Database_Abstract $instance) : Database_Abstract
    {
        self::$instance = $instance;
        return $instance;
    }

    public static function instance() : Database_Abstract
    {
        if (!isset(self::$instance)) {
            throw new Exception("DBConnection is not initialized");
        }
        return self::$instance;
    }

    public static function setTablePrefix($prefix)
    {
        $db = self::instance();
        $db->setPrefix($prefix);
    }
}

abstract class Database_Abstract
{
    const DBTYPE = '';
    protected static $readonlyProps = ['prefix', 'lastQuery'];

    /** @var string */
    protected $prefix = '';

    /** @var string */
    protected $lastQuery = '';

    abstract function connect(array $params): void;
    abstract function sq(string $query, ?array $values = null);
    abstract function sqa(string $query, ?array $values = null): ?array;
    abstract function dq(string $query, ?array $values = null): DatabaseResult_Abstract;
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
        if ( in_array($propName, self::$readonlyProps) ) {
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
}

abstract class DatabaseResult_Abstract
{
    abstract function fetchRow(): ?array;
    abstract function fetchAssoc(): ?array;
}

