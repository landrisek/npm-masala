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

    public function explainColumn($table, $column) {
        return $this->database->query('EXPLAIN SELECT `' . $column . '` FROM `' . $table . '`')->fetch();
    }

    public function getColumns($table) {
        return $this->database->getConnection()
                        ->getSupplementalDriver()
                        ->getColumns($table);
    }

    public function getPrimary($table) {
        return $this->database->table($table)
                    ->getPrimary();
    }
    
    public function getTestRow($table, Array $clauses = []) {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->order('RAND()')
                        ->fetch();
    }

    public function getTestRows($table, Array $clauses = [], $limit) {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->order('RAND()')
                        ->limit($limit)
                        ->fetchAll();
    }

    public function getTestTables() {
        $tables = $this->database->query('SHOW TABLES;')
                ->fetchAll();
        shuffle($tables);
        return $tables;
    }

    public function removeTestRow($table, Array $clauses = []) {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->delete();
    }

}
