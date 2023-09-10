<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace BackupExtension;

use Exception;

class Backup
{
    public $lastErrorString = null;
    public $filename;
    private $fh;
    private $level = 0;
    private $tagClosed = true;

    function __construct(?string $filename)
    {
        $this->filename = is_null($filename) ?  MTTPATH. 'db/backup.xml' : $filename;
    }

    function isFileWritable()
    {
        if (!file_exists($this->filename)) {
            @touch($this->filename);
        }
        if (!is_writable($this->filename)) {
            return false;
        }
        return true;
    }

    function makeBackup()
    {
        if (!$this->isFileWritable()) {
            $this->lastErrorString = __('backup.not_writable');
            return false;
        }

        $this->fh = fopen($this->filename, 'w');
        if ($this->fh === false) {
            $ea = error_get_last();
            $this->lastErrorString = $ea['message'] ?? "Failed to open file for writing";
            return false;
        }

        $db = \DBConnection::instance();

        fwrite($this->fh, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
        $this->writeOpeningTag('mttdb', [
            'version' => 1,
            'appversion' => \mytinytodo\Version::VERSION,
            'dbversion' => \mytinytodo\Version::DB_VERSION,
            'dbtype' => $db::DBTYPE,
            'created' => date(DATE_ATOM)
        ]);
        $this->level = 0;


        $this->writeTable($db->prefix.'lists', 'lists', 'list');
        $this->writeTable($db->prefix.'todolist', 'tasks', 'task');
        $this->writeTable($db->prefix.'tags', 'tags', 'tag');
        $this->writeTable($db->prefix.'tag2task', 'tag2task', 'item');
        $this->writeTable($db->prefix.'settings', 'settings', 'item');


        $this->writeClosingTag('mttdb');
        fwrite($this->fh, "\n");

        if (!fclose($this->fh)) {
            $ea = error_get_last();
            $this->lastErrorString = $ea['message'] ?? "Failed to close file";
            return false;
        }
        return true;
    }

    function writeTable(string $table, string $group, string $itemName)
    {
        if (!preg_match("/^[\\w:]+$/", $table)) {
            throw new Exception("Malformed table name: $table");
        }
        $db = \DBConnection::instance();
        $props = null;
        if ($db::DBTYPE == \DBConnection::DBTYPE_MYSQL) {
            $autoinc = $this->getMysqlTableAutoIncrement($table);
            if ($autoinc != '') {
                $props = ['auto_increment' => $autoinc];
            }
        }
        $this->writeOpeningTag($group, $props);
        $q = $db->dq("SELECT * FROM $table");
        while ($r = $q->fetchAssoc()) {
            $this->writeItem($itemName, $r);
        }
        $this->writeClosingTag($group);
    }

    function writeItem(string $entity, $r)
    {
        $tagAttrs = null;
        if (isset($r['id'])) {
            $tagAttrs = ['id' => $r['id']];
            unset($r['id']);
        }
        $this->writeOpeningTag($entity, $tagAttrs);
        foreach ($r as $field => $value) {
            $props = null;
            if (is_null($value)) {
                $props['isnull'] = 'yes';
            }
            $this->writeOpeningTag($field, $props);
            $this->writeTagContent((string)$value);
            $this->writeClosingTag($field);
        }
        $this->writeClosingTag($entity);
    }

    function getMysqlTableAutoIncrement(string $table): string
    {
        $db = \DBConnection::instance();
        $r = $db->sqa("SHOW TABLE STATUS WHERE Name=?", [$table]);
        return (string)$r['Auto_increment'] ?? '';
    }



    function writeOpeningTag(string $tag, ?array $attrs = null)
    {
        if (!preg_match("/^[\\w:]+$/", $tag)) {
            throw new Exception("Malformed tag: $tag");
        }
        $data = "<$tag";
        if ($attrs !== null) {
            $a = [];
            foreach ($attrs as $k => $v) {
                if (!preg_match("/^[\\w:-]+$/", $k)) {
                    throw new Exception("Malformed attribute name: $k");
                }
                $v = (string)$v;
                if (preg_match("/[\\r\\n]+/", $v)) {
                    throw new Exception("Malformed attribute value: $v");
                }
                $a[] = "$k=\"". htmlspecialchars($v). "\"";
            }
            if (count($a) > 0) {
                $data .= " ". implode(" ", $a);
            }
        }
        $data .= ">";
        $this->write( ($this->tagClosed ? "" : "\n"). str_repeat(' ', $this->level) . $data );
        $this->level += 1;
        $this->tagClosed = false;
    }

    function writeClosingTag(string $tag)
    {
        if (!preg_match("/^[\\w:]+$/", $tag)) {
            throw new Exception("Malformed tag: $tag");
        }
        $this->level -= 1;
        if ($this->level < 0) $this->level = 0;
        $padding = '';
        if ($this->tagClosed) {
            $padding = str_repeat(' ', $this->level);
        }
        $this->write( $padding . "</$tag>\n" );
        $this->tagClosed = true;
    }

    function writeTagContent(?string $content)
    {
        if ($content !== null) {
            $this->write( htmlspecialchars($content, ENT_XML1, 'UTF-8') ); //TODO: make xml compliant?
        }
    }

    function write(string $data)
    {
        if (false === @fwrite($this->fh, $data)) {
            $ea = error_get_last();
            throw new Exception("Failed to write to file: ". ($ea['message'] ?? "unknown reason"));
        }
    }

}
