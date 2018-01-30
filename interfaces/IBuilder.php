<?php

namespace Masala;

interface IBuilder {

    public function getOffsets(): array;

    public function getSum(): int;

    public function prepare(): IBuilder;

}
