<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IImport {

    public function save(array $response): void;

}
