<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IBuilder {

    public function getOffsets(): array;

    public function getSum(): int;

    public function prepare(): IBuilder;

}
