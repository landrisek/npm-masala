<?php

namespace Masala;

use Nette\Database\Table\IRow;

/** @author Lubomir Andrisek */
final class MockRepository extends BaseRepository implements IMock {

    /** @return IRow */
    public function explainColumn($table, $column) {
        return $this->database->query('EXPLAIN SELECT `' . $column . '` FROM `' . $table . '`')->fetch();
    }

    /** @return array */
    public function getColumns($table) {
        return $this->database->getConnection()
                        ->getSupplementalDriver()
                        ->getColumns($table);
    }

    /** @return IRow */
    public function getDuplicity($table, $group, array $columns) {
        $resource = $this->database->table((string) $table)
                        ->select($group . ', COUNT(id) AS sum');
        foreach($columns as $column => $value) {
            $resource->where($column, $value);
        }
        if(null == $row = $resource->having('sum > 1')->group($group)->fetch()) {
            return new EmptyRow();
        }
        return $row;
    }

    /** @retrun string|array */
    public function getPrimary(string $table) {
        return $this->database->table($table)
                    ->getPrimary();
    }

    public function getTestRow(string $table, array $columns = [], $select = null, $order = null): IRow {
        $resource = $this->database->table($table);
        empty($select) ?  null : $resource->select($select);
        foreach ($columns as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        empty($order) ? $resource->order('RAND()') : $resource->order($order);
        if(null == $row  = $resource->limit(1)->fetch()) {
            return new EmptyRow();
        }
        return $row;
    }

    public function getTestRows($table, array $clauses = [], $limit): array {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->order('RAND()')
                        ->limit($limit)
                        ->fetchAll();
    }

    public function getTestTables(): array {
        $tables = $this->database->query('SHOW TABLES;')->fetchAll();
        shuffle($tables);
        return $tables;
    }

     public function updateTestRow($table, array $data, array $clauses = []): int {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->update($data);
    }

    public function removeTestRow($table, array $clauses = []): int {
        $resource = $this->database->table($table);
        foreach ($clauses as $column => $value) {
            is_bool($value) ? $resource->where($column) : $resource->where($column, $value);
        }
        return $resource->delete();
    }

}
