<?php

namespace Masala;

interface IFetch {

    /** @return array */
    public function fetch(IBuilder $builder);

    /** @return int */
    public function sum(IBuilder $builder);

}
