<?php

namespace Masala;

use Nette\Database\Table\IRow;

/** @author Lubomir Andrisek */
final class KeywordsRepository extends BaseRepository {

    public function getKeyword(sring $keyword, array $used): IRow {
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
