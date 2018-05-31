<?php

namespace Masala;

use Nette\ComponentModel\IComponent,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class RowForm extends ReactForm implements IRowFormFactory {

    public function __construct(string $css, string $js, IRequest $request, ITranslator $translatorModel) {
        parent::__construct($css, $js, $request);
    }

    public function create(): ReactForm {
        return $this;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
    }

}

interface IRowFormFactory {

    public function create(): ReactForm;
}
