<?php

namespace Masala;

interface IHelpModel {

    /** @return array */
    function getHelp($controller, $action, $parameters);
    
}
