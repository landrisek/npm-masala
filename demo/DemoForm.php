<?php

namespace Masala;

use Masala\ReactForm,
    Nette\Application\IPresenter,
    Nette\Application\UI\Control,
    Nette\ComponentModel\IComponent,
    Nette\Localization\ITranslator,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class DemoForm extends ReactForm implements IDemoFormFactory {

    /** @var ITranslator */
    private $translatorRepository;

    public function __construct(ITranslator $translatorRepository, IRequest $request) {
        parent::__construct($request);
        $this->translatorRepository = $translatorRepository;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            $this->addTitle('title', ['value' => $this->translatorRepository->translate('If you have a minute, let us know why are you subscribing.'),
                                      'class' => 'unsubscribe'])
                    ->addRadioList('answer', ['data' => ['test'], 'onClick' => 'click()'])
                    ->addText('content', ['data' => ['delay' => 0],
                                            'onBlur' => 'change()',
                                            'onChange' => 'change()',
                                            'style' => ['display'=>'none'], 
                                            'placeholder' => $this->translatorRepository->translate('You can write your opinion here.'),
                                            'value' => ''], 
                                        ['required' => $this->translatorRepository->translate('Please fill your question.')])
                    ->addSubmit('submit');
        }
    }

    public function create(): ReactForm {
        return $this;
    }

    public function submit(Control $control) {
        
    }
    
    public function succeeded(array $data) {
        $parameters = parent::succeeded($data);
        die();
    }

}

interface IDemoFormFactory {

    public function create(): ReactForm;
}
