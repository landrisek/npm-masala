<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IRemove {

    public function remove(array $primary, array $response): array;

}
