<?php

namespace Masala;

interface IListener {

    /** @return array */
    function getKeys();

    /** @return array */
    function listen(array $response);

}
