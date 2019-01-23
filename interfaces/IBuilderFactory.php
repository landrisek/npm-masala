<?php

namespace Masala;

use Nette\Application\UI\Control;

/** @author Lubomir Andrisek */
interface IBuilderFactory {

    public function handleState(): void;

    public function props(): array;

}
