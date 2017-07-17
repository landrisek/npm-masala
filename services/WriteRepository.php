<?php

namespace Masala;

/** @author Lubomir Andrisek */
final class WriteRepository extends BaseRepository {

    /** @return int */
    public function updateWrite($keyword, array $data) {
        return $this->database->table($this->source)
                        ->where('keyword', (string) $keyword)
                        ->update($data);
    }

}
