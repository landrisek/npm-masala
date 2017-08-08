<?php

namespace Masala;

interface IFetch {

    /** @return array */
    function fetch(IBuilder $builder);

}
