<?php

namespace Masala;

/** @author Lubomir Andrisek */
final class KeywordsRepository extends BaseRepository {

    /** @return \Nette\Database\Table\IRow */
    public function getKeyword($keyword) {
        if(false == $row = $this->database->table($this->source)
                    ->where('content LIKE', '%"' . strtolower($keyword) . '"%')
                    ->fetch()) {
            return new EmptyRow();
        }
        return $row;
    }

}
