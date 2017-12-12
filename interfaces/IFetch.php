<?php

namespace Masala;

interface IFetch {

    /** @return array */
    function fetch(IBuilder $builder);

    /** @return int */
    function sum(IBuilder $builder);

}
