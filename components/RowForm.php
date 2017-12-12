<?php

namespace Masala;

use Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class RowForm extends ReactForm implements IRowFormFactory {

    /** @var string */
    private $jsDir;

    public function __construct($jsDir, IRequest $request, ITranslator $translatorModel) {
        parent::__construct(Types::string($jsDir), $request, $translatorModel);
        $this->jsDir = Types::string($jsDir);
    }

    /** @return IRowFormFactory */
    public function create() {
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
    }

}

interface IRowFormFactory {

    /** @return RowForm */
    function create();
}
