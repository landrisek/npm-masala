<?php

namespace Masala;

use Nette\ComponentModel\IComponent,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class RowForm extends ReactForm implements IRowFormFactory {

    /** @var string */
    private $jsDir;

    public function __construct(string $jsDir, IRequest $request, ITranslator $translatorModel) {
        parent::__construct($jsDir, $request, $translatorModel);
        $this->jsDir = $jsDir;
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
