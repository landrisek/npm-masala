<?php

namespace Masala;

use Nette\Caching\Cache,
    Nette\Caching\IStorage,
    Nette\Database\Context,
    Nette\Object;

/** @author Lubomir Andrisek */
final class WriteModel extends Object {

    /** @var Cache */
    public $cache;

    /** @var Context */
    public $database;

    /** @var string */
    private $source;

    public function __construct($source, Context $database, IStorage $storage) {
        $this->source = $source;
        $this->database = $database;
        $this->cache = new Cache($storage);
    }
    
    public function getSource() {
        return $this->source;
    }

    /** @return int */
    public function updateWrite($id, array $data) {
        return $this->database->table($this->source)
                        ->where('id', $id)
                        ->update($data);
    }

}
