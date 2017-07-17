<?php

namespace Masala;

use Nette\Database\Table\IRow;

interface IMock {

    /** @return IRow */
    function getTestRow($table, array $clauses = []);

    /** @return array */
    function getTestRows($table, array $clauses = [], $limit);
    
}
