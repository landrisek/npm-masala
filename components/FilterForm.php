<?php

namespace Masala;

use Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class FilterForm extends ReactForm implements IFilterFormFactory {

    public function __construct(string $css, string $js, IRequest $request) {
        parent::__construct($css, $js, $request);
    }

    public function create(): ReactForm {
        return $this;
    }

}

interface IFilterFormFactory {

    public function create(): ReactForm;
}
