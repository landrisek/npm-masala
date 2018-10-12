<?php

namespace Masala;

use Iterator,
    Nette\Database\Table\GroupedSelection,
    Nette\Database\Table\IRow,
    Nette\Database\Table\Selection;

/** @author Lubomir Andrisek */
class EmptyRow implements Iterator, IRow {

    public function setTable(Selection $name) {}

    public function getTable(): Selection {}

	/**@return mixed */
    public function getPrimary(bool $throw = true) {}

    public function getSignature(bool $throw = true): string { return ''; }

    public function related(string $key, ?string $throughColumn = null): GroupedSelection {}

    public function ref(string $key, ?string $throughColumn = null): ?IRow { }

	/** @return mixed */
    public function current() {}

    /** @return void */
    public function next() {}

    /** @return mixed */
    public function key() {}

    /** @return boolean  */
    public function valid() {}

    /** @return void  */
    public function rewind() {}

    /** @return boolean */
    public function offsetExists($offset) {}

    /** @return mixed */
    public function offsetGet($offset) {}

    /** @return void */
    public function offsetSet($offset, $value) {}

    /** @return void */
    public function offsetUnset($offset) {}

    /** @retrun array */
    public function toArray() {
        return [];
    }
}
