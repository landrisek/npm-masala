<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IHelp {

    public function getHelp(string $controller, string $action, string $parameters): array;
    
}
