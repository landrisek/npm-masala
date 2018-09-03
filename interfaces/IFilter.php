<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IFilter {

    public function getList(string $alias): array;

    public function filter(array $filters): array;

}
