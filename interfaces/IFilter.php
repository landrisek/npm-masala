<?php

namespace Masala;

interface IFilter {

    /** @return array */
    public function getList($alias);

    /** @return array */
    public function filter(array $filters);

}
