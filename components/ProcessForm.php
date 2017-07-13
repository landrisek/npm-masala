<?php

namespace Masala;

use Nette\Application\IPresenter,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class ProcessForm extends ReactForm implements IProcessFormFactory {

    /** @var ITranslator */
    private $translatorModel;

    /** @var IProcess */
    private $service;

    public function __construct($jsDir, IRequest $request, ITranslator $translatorModel) {
        parent::__construct($jsDir, $request, $translatorModel);
        $this->translatorModel = $translatorModel;
    }

    /** @return IProcessFormFactory */
    public function create() {
        return $this;
    }

    public function setService(IProcess $service) {
        $this->service = $service;
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter and false == $this->isSignalled()) {
            $this->addProgressBar('prepare');
            $this->service->attached($this);
            $this->addSubmit('prepare', ucfirst($this->translatorModel->translate('process')),
                ['class' => 'btn btn-success',
                    'onClick' => 'prepare']);
            $this->addMessage('done', $this->translatorModel->translate('Lengthy process was completed.'));
        }
    }

}

interface IProcessFormFactory {

    /** @return ProcessForm */
    function create();
}
