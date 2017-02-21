<?php

namespace Masala;

use Nette\Caching\Cache,
    Nette\Database\Context,
    Nette\Database\Table\ActiveRow,
    Nette\Caching\IStorage;

/** @author Lubomir Andrisek */
final class HelpModel implements IHelp {

    /** @var Cache */
    public $cache;

    /** @var Context */
    public $database;

    /** @var string */
    public $source;

    public function __construct($source, Context $database, IStorage $storage) {
        $this->source = $source;
        $this->database = $database;
        $this->cache = new Cache($storage);
    }

    /** @return ActiveRow */
    public function getHelp($controller, $action) {
        if (false == $help = $this->database->table($this->source)
                ->select('*')
                ->where('source = ? OR source = ?', $controller, $controller . ':' . $action)
                ->fetch()) {
            return [];
        }
        return json_decode($help->json);
    }

}
