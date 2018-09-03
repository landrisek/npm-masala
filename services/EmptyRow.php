<?php

namespace Masala;

use Iterator,
    Nette\Database\Table\GroupedSelection,
    Nette\Database\Table\IRow,
    Nette\Database\Table\Selection;

/** @author Lubomir Andrisek */
final class EmptyRow implements Iterator, IRow {

    public function setTable(Selection $name) {}

    public function getTable(): Selection {}

    /**@return mixed */
    public function getPrimary(bool $throw = true) {}

    public function getSignature(bool $throw = true): string { return ''; }

    public function related(string $key, ?string $throughColumn = null): GroupedSelection {}

    public function ref(string $key, ?string $throughColumn = null): ?IRow { }

    /** @return mixed */
    public function current() {}

    public function next(): void {}

    /** @return mixed */
    public function key() {}

    public function offsetExists($offset): bool {}

    public function offsetGet($offset) {}

    public function offsetSet($offset, $value): void {}

    public function offsetUnset($offset): void {}

    public function rewind(): void {}

    public function toArray(): array {
        return [];
    }

    public function valid(): bool {}

}
