<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IUpdate {

    public function update(string $key, array $data): array;

}
