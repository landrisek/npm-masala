<?php

namespace Masala;

use Nette\Caching\Cache,
    Nette\Caching\IStorage,
    Nette\Database\Context,
    Nette\Object;

/** @author Lubomir Andrisek */
final class MockModel extends Object {

    /** @var Cache */
    public $cache;

    /** @var Context */
    public $database;

    public function __construct(Context $database, IStorage $storage) {
        $this->database = $database;
        $this->cache = new Cache($storage);
    }

    /** getters */
    public function getTestRow($table, Array $clauses = []) {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->order('RAND()')
                        ->fetch();
    }

    public function getTestTables() {
        return $this->database->query('SHOW TABLES;')
                        ->fetchAll();
    }

     public function explainColumn($table, $column) {
        return $this->database->query('EXPLAIN SELECT `' . $column . '` FROM `' . $table . '`')->fetch();
    }

    /** delete */
    public function removeTestRow($table, Array $clauses = []) {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->delete();
    }
}
