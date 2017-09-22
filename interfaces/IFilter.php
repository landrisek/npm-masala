<?php

namespace Masala;

interface IFilter {

    /** @return array */
    function getList($alias);

    /** @return array */
    function filter(array $filters);

}
