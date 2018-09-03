<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IListener {

    function getKeys(): array;

    function listen(array $response): array;

}
