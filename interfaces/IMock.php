<?php

namespace Masala;

use Nette\Database\Table\IRow;

/** @author Lubomir Andrisek */
interface IMock {

    public function getTestRow(string $table, array $clauses = []): IRow;

    public function getTestRows(string $table, array $clauses, int $limit): array;
    
}
