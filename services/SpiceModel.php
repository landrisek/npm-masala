<?php

namespace Masala;

use Nette\Caching\Cache,
    Nette\Caching\IStorage,
    Nette\Database\Context,
    Nette\Object;

/** @author Lubomir Andrisek */
final class SpiceModel extends Object {

    /** @var Cache */
    private $cache;

    /** @var Context */
    private $database;

    /** @var string */
    private $source;

    public function __construct($source, Context $database, IStorage $storage) {
        $this->source = (string) $source;
        $this->database = $database;
        $this->cache = new Cache($storage);
    }

    /** getters */
    public function getOffset($query) {
        return $this->database->query($query);
    }

    public function getView($query, Array $arguments) {
        return $this->database->query((string) $query, ...$arguments)
                    ->fetch();
    }

    public function getViewBySource($source) {
        return $this->database->table($this->source)
                    ->where('source', (string) $source)
                    ->where('views_id IS NOT NULL')
                    ->fetch();
    }

    public function getSpice($source) {
        return $this->database->table($this->source)
                    ->where('source', (string) $source)
                    ->order('id DESC')
                    ->fetch();
    }

    /** insert */
    public function addView($table, Array $data) {
        return $this->database->table($table)
                    ->insert($data);
    }

}
