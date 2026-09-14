<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023-2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace BackupExtension;

use XMLReader;
use DBConnection;
use Exception;

use BackupExtension\Backup;

class Restore
{
    const supportedDbVersions = ['1.8', '2.0'];

    public ?string $lastErrorString = null;
    private string $filename;
    private XMLReader $reader;
    private array $tableItem;
    private string $dbVersion;
    private int $userId;
    private array $endRestoreQueries = [];
    private array $autoincState = [];

    function __construct()
    {
        // xml table => [ db table, xml item ]
        $this->tableItem = [
            'lists' => ['lists', 'list'],
            'tasks' => ['todolist', 'task'],
            'tags' => ['tags', 'tag'],
            'tag2task' => ['tag2task', 'item'],
            'settings' => ['settings', 'item'],
            'users' => ['users', 'user'],
            'usersettings' => ['usersettings', 'item'],
        ];
    }

    function isUploaded(): bool
    {
        if (!isset($_FILES['file']) || !isset($_FILES['file']['name']) || !isset($_FILES['file']['tmp_name'])) {
            $this->lastErrorString = "Not uploaded";
            return false;
        }

        $this->filename = $_FILES['file']['tmp_name'];
        if (!file_exists($this->filename) || !is_readable($this->filename)) {
            $this->lastErrorString = "Can't open file";
            return false;
        }
        return true;
    }

    function isLocal(string $filename): bool
    {
        $this->filename = $filename;
        if (!file_exists($filename)) {
            $this->lastErrorString = "Backup file does not exists";
            return false;
        }
        return true;

    }

    function restore(): bool
    {
        if (\MTTVersion::DB_VERSION !== '2.0') {
            $this->lastErrorString = "Running on unsupported version";
            return false;
        }
        $this->userId = userId() ?: 1;

        $this->reader = $reader = new XMLReader();
        $reader->open($this->filename);

        // root element
        $reader->next('mttdb');
        if ($reader->name != 'mttdb') {
            $this->lastErrorString = "Incorrect format: missing 'mttdb'.";
            return false;
        }

        $this->dbVersion = $this->reader->getAttribute("dbversion");
        if (!in_array($this->dbVersion, static::supportedDbVersions)) {
            $this->lastErrorString = "Unsupported database version in backup file";
            return false;
        }

        if ($this->dbVersion === '1.8') {
            // dont touch tables not available in old version
            unset($this->tableItem['users']);
            unset($this->tableItem['usersettings']);
        }

        if (!$this->moveNextElement()) {
            $this->lastErrorString = "Incorrect format: tables not found.";
            return false;
        }

        $this->beginRestore();

        $tables = array_keys($this->tableItem);

        // Enumerate tables
        do {
            if ($reader->nodeType != XMLReader::ELEMENT) {
                error_log("Unexpected element '{$reader->name}' of type: {$reader->nodeType}");
                break;
            }
            //error_log("Found table '{$reader->name}'");

            $result = null;
            if (in_array($reader->name, $tables)) {
                $result = $this->readTable($this->tableItem[$reader->name][0], $this->tableItem[$reader->name][1]);
            }
            else {
                continue; // Unexpected table, just skip
            }
            if (is_null($result)) {
                $this->cancelRestore();
                return false; // Incorrect format, error is set, stop
            }

        } while ($this->moveNextElementSameLevel());

        if ($this->dbVersion === '1.8') {
            $this->update18to20();
        }

        $this->endRestore();

        $reader->close();
        return true;
    }

    function moveNextElement(?string $el = null): ?bool
    {
        while ($this->reader->read()) {
            if ($this->reader->nodeType == XMLReader::ELEMENT) {
                if (!is_null($el) && $this->reader->name != $el) {
                    return false;
                }
                return true;
            }
            else if ($this->reader->nodeType == XMLReader::END_ELEMENT) {
                return null;
            }
        }
        return null;
    }

    function moveNextElementSameLevel(?string $el = null)
    {
        return $this->reader->next() && ($this->reader->nodeType == XMLReader::ELEMENT || $this->moveNextElement($el));
    }

    function readTable(string $table, string $itemName): ?int
    {
        $autoinc = $this->reader->getAttribute("auto_increment");
        $maxId = 0;
        $count = 0;

        // find first item
        $found = $this->moveNextElement($itemName);
        if ($found === false) {
            $this->lastErrorString = "Incorrect item name {$this->reader->name}, expected '{$itemName}'";
            return null; // Error
        }
        else if (is_null($found)) {
            return 0; // No items found
        }

        do {
            $count++;
            $id = $this->reader->getAttribute("id");
            if (!is_null($id)) {
                $maxId = max($maxId, (int)$id);
            }
            // error_log("# $count: found $itemName with id $id");

            $itemXml = $this->reader->readOuterXml();
            $xml = simplexml_load_string($itemXml); //SimpleXMLElement
            if ($xml === false) {
                error_log("Incorrect format of $itemName");
                continue;
            }
            if (!$this->insertToTable($table, $xml)) {
                return null; // Error
            }

        } while ($this->moveNextElementSameLevel($itemName));

        // restore table last auto_increment (mysql)
        if (!is_null($autoinc)) {
            $autoinc = max((int)$autoinc, $maxId);
        }
        else {
            $autoinc = $maxId + 1;
        }
        if ($autoinc > 1) {
            $this->updateAutoinc($table, $autoinc);
        }
        return $count;
    }

    private function insertToTable(string $table, \SimpleXMLElement $xml): bool
    {
        if (!preg_match("/^[\\w]+$/", $table)) {
            throw new Exception("Malformed table name: $table");
        }
        $fields = [];
        $values = [];

        $attrsXml = $xml->attributes();
        if (isset($attrsXml['id']) && $attrsXml['id'] != '') {
            $fields[] = 'id';
            $values[] = (string)$attrsXml['id'];
        }

        foreach ($xml->children() as $item) {
            $field = $item->getName();
            $value = (string)$item;
            if (!preg_match("/^[\\w]+$/", $field)) {
                throw new Exception("Malformed field name: $field");
            }
            $attrsXml = $item->attributes();
            if (isset($attrsXml['isnull']) && $attrsXml['isnull'] == 'yes') { //$attrsXml['isnull']->__toString()
                $value = null;
            }
            $fields[] = $field;
            $values[] = $value;
        }

        $fieldsStr = implode(',', $fields); // id,name,title ...
        $subsStr = implode(',', array_fill(0, count($fields), '?')); // ?,?,? ...
        $db = DBConnection::instance();
        try {
            $db->ex("INSERT INTO {$db->prefix}{$table} ($fieldsStr) VALUES ($subsStr)", $values);
        }
        catch (Exception $e) {
            error_log("Failed query: {$db->lastQuery}");
            $idInfo = ($fields[0] === 'id') ? "Record with ID {$values[0]}." : '';
            $this->lastErrorString = "Failed to add data to table '{$db->prefix}$table'. $idInfo Database error (see query in php error log): ". $e->getMessage();
            return false;
        }
        return true;
    }

    private function updateAutoinc(string $table, int $autoinc)
    {
        $db = DBConnection::instance();
        switch ($db::DBTYPE) {
            case DBConnection::DBTYPE_MYSQL:
                //because mysql will autocommit on alter table
                $this->endRestoreQueries[] = "ALTER TABLE {$db->prefix}$table AUTO_INCREMENT = ". (int)$autoinc;
                break;
            case DBConnection::DBTYPE_POSTGRES:
                $db->ex("ALTER TABLE {$db->prefix}$table ALTER COLUMN id RESTART WITH ". (int)$autoinc);
                break;
            case DBConnection::DBTYPE_SQLITE:
                $db->ex("UPDATE sqlite_sequence SET seq=? WHERE name=?", [$autoinc, $db->prefix. $table]);
                break;
            default:
                break;
        }
    }

    private function beginRestore()
    {
        $db = DBConnection::instance();
        $db->ex("BEGIN");
        foreach ($this->tableItem as $a) {
            $table = $db->prefix. $a[0];
            if ($db::DBTYPE == DBConnection::DBTYPE_POSTGRES) {
                $db->ex("TRUNCATE TABLE $table RESTART IDENTITY");
            }
            else {
                if ($db::DBTYPE === DBConnection::DBTYPE_MYSQL) {
                    #save autoincrement for rollback
                    $autoinc = (int)Backup::getTableAutoIncrement($table);
                    if ($autoinc > 0) {
                        $this->autoincState[$table] = $autoinc;
                    }
                }
                # - we do not use TRUNCATE on mysql due to autocommit
                # - sqlite has truncate optimizer while delete all to make it faster
                # - no need to reset auto_increment sequence before inserting lower ids
                $db->ex("DELETE FROM $table");
            }
        }
        $db->ex("DELETE FROM {$db->prefix}sessions");
    }

    private function endRestore()
    {
        $db = DBConnection::instance();
        $db->ex("COMMIT");
        // vacuum? optimize?
        foreach ($this->endRestoreQueries as $query) {
            $db->ex($query);
        }
    }

    private function cancelRestore()
    {
        $db = DBConnection::instance();
        $db->ex("ROLLBACK");
        foreach ($this->autoincState as $table => $autoinc) {
            $db->ex("ALTER TABLE $table AUTO_INCREMENT = ". (int)$autoinc);
        }
        // vacuum? optimize?
    }

    private function update18to20()
    {
        $db = DBConnection::instance();
        $db->ex("UPDATE {$db->prefix}lists SET user_id = ?", [$this->userId]);
        $db->ex("UPDATE {$db->prefix}tags SET user_id = ?", [$this->userId]);
        // $db->ex("INSERT INTO {$db->prefix}usersettings (user_id,param_key,param_value)
        //         SELECT ?,param_key,param_value FROM {$db->prefix}settings WHERE param_key='alltasks.json' ", [$this->userId]);
        $db->ex("DELETE FROM {$db->prefix}settings WHERE param_key='alltasks.json'");
    }

}
