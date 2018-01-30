<?php

namespace Masala;

use Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class FilterForm extends ReactForm implements IFilterFormFactory {

    /** @var IRequest */
    private $request;

    /** @var ITranslator */
    private $translatorRepository;
    
    public function __construct(string $jsDir, IRequest $request, ITranslator $translatorRepository) {
        parent::__construct($jsDir, $request, $translatorRepository);
        $this->request = $request;
        $this->translatorRepository = $translatorRepository;
    }

    public function create(): ReactForm {
        return $this;
    }

}

interface IFilterFormFactory {

    public function create(): ReactForm;
}
