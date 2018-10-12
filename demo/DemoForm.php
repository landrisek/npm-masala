<?php

namespace Masala;

use Masala\ReactForm,
    Nette\Application\IPresenter,
    Nette\Application\UI\Control,
    Nette\Localization\ITranslator,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class DemoForm extends ReactForm implements IDemoFormFactory {

    /** @var TranslatorModel */
    private $translatorModel;

    public function __construct(ITranslator $translatorModel, IRequest $request) {
        parent::__construct($request);
        $this->translatorModel = $translatorModel;
    }

    /** @return ISurveyFormFactory */
    public function create() {
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            $this->addTitle('title', ['value' => $this->translatorModel->translate('If you have a minute, let us know why are you subscribing.'),
                                      'class' => 'unsubscribe'])
                    ->addRadioList('answer', ['data' => ['test'], 'onClick' => 'click()'])
                    ->addText('content', ['data' => ['delay' => 0],
                                            'onBlur' => 'change()',
                                            'onChange' => 'change()',
                                            'style' => ['display'=>'none'], 
                                            'placeholder' => $this->translatorModel->translate('You can write your opinion here.'),
                                            'value' => ''], 
                                        ['required' => $this->translatorModel->translate('Please fill your question.')])
                    ->addSubmit('submit');
        }
    }

    public function submit(Control $control) {
        
    }
    
    public function succeeded(array $data) {
        $parameters = parent::succeeded($data);
        die();
    }

}

interface IDemoFormFactory {

    /** @return DemoForm */
    function create();
}
