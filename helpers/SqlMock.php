<?php

namespace Masala;

use Nette\Database\Context;
use Nette\Database\IRow;
use Nette\Database\Table\IRow as TableRow;

/** @author Lubomir Andrisek */
final class SqlMock {

    /** @var Context */
    private $database;

    public function __construct(Context $database) {
        $this->database = $database;
    }

    public function explainColumn(string $table, string $column): IRow {
        return $this->database->query('EXPLAIN SELECT `' . $column . '` FROM `' . $table . '`')->fetch();
    }

    public function getColumns(string $table): array {
        return $this->database->getConnection()
                        ->getSupplementalDriver()
                        ->getColumns($table);
    }

    public function getDuplicity(string $table, string $group, array $columns): TableRow {
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

    public function getPrimary(string $table): ?array {
        return $this->database->table($table)
                    ->getPrimary();
    }

    public function getTestRow(string $table, array $columns = [], $select = null, $order = null): TableRow {
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

    public function getTestRows(string $table, array $clauses, int $limit): array {
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