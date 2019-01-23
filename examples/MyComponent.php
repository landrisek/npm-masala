<?php

namespace Masala;

use Nette\Localization\ITranslator,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class MyComponent extends Control implements IMyComponentFactory {

    /** @var TranslatorModel */
    private $translatorModel;

    public function __construct(ITranslator $translatorModel, IRequest $request) {
        parent::__construct($request);
        $this->translatorModel = $translatorModel;
    }

    public function create(): MyComponent {
        return $this;
    }

    public function props(): array {
        return parent::props();
    }

    public function handleSubmit() {
        return parent::handleSubmit();
    }

}

interface IMyComponentFactory {

    public function create(): MyComponent;
}
