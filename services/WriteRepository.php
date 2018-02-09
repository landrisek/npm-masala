<?php

namespace Masala;

use Nette\Database\Table\IRow;

/** @author Lubomir Andrisek */
final class WriteRepository extends BaseRepository {

    public function getWrite(string $keyword): IRow {
        return $this->database->table($this->source)
                    ->where('keyword', $keyword)
                    ->fetch();
    }

    public function updateWrite(string $keyword, array $data): int {
        return $this->database->table($this->source)
                        ->where('keyword', $keyword)
                        ->update($data);
    }

}
