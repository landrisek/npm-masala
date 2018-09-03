<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IContent {

    function getKeywords(string $like): array;

}
