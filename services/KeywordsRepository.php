<?php

namespace Masala;

use Nette\Database\Table\IRow;

/** @author Lubomir Andrisek */
final class KeywordsRepository extends BaseRepository {

    /** @return IRow */
    public function getKeyword($keyword, array $used) {
        $resource = $this->database->table($this->source)
                        ->where('content LIKE',  '%' . strtolower($keyword) . '%');
        foreach($used as $usage) {
            $resource->where('content NOT LIKE', '%' . strtolower($usage) . '%');
        }
        if(null == $row = $resource->fetch()) {
            return new EmptyRow();
        }
        return $row;
    }

}
