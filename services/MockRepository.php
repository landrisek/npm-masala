<?php

namespace Masala;

/** @author Lubomir Andrisek */
final class MockRepository extends BaseRepository implements IMock {

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

    /** @return IRow */
    public function getTestRow($table, array $clauses = []) {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->order('RAND()')
                        ->fetch();
    }

    /** @return array */
    public function getTestRows($table, array $clauses = [], $limit) {
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
