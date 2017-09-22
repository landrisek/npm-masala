<?php

namespace Masala;

interface IButton {

    /** @return array */
    function getButtons();

    /** @return array */
    function push(array $response);

}
