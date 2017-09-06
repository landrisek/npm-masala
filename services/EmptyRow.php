<?php

namespace Masala;

use Iterator,
    Nette\Database\Table\GroupedSelection,
    Nette\Database\Table\Selection;

/** @author Lubomir Andrisek */
class EmptyRow implements Iterator, \Nette\Database\Table\IRow {

    public function setTable(Selection $name) {}

	/** @return Selection */
    public function getTable() {}

	/**@return mixed */
    public function getPrimary($throw = true) {}

	/** @return string */
    public function getSignature($throw = true) { return ''; }

	/** @return GroupedSelection */
    public function related($key, $throughColumn = null) {}

	/** @return IRow|null */
    public function ref($key, $throughColumn = null) { }

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
