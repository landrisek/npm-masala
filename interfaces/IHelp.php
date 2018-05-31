<?php

namespace Masala;

interface IHelp {

    function getHelp(string $controller, string $action, string $parameters): array;
    
}
