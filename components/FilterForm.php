<?php

namespace Masala;

use Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class FilterForm extends ReactForm implements IFilterFormFactory {

    /** @var IRequest */
    private $request;

    /** @var ITranslator */
    private $translatorModel;
    
    public function __construct($jsDir, IRequest $request, ITranslator $translatorModel) {
        parent::__construct($jsDir, $request, $translatorModel);
        $this->request = $request;
        $this->translatorModel = $translatorModel;
    }

    /** @return IFilterFormFactory */
    public function create() {
        return $this;
    }

}

interface IFilterFormFactory {

    /** @return FilterForm */
    function create();
}
