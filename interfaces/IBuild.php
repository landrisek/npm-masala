<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IBuild {

    public function build(array $row): array;

}
