<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IFetch {

    public function fetch(IBuilder $builder): array;

    public function sum(IBuilder $builder): int;

}
