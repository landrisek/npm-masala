<?php

namespace Masala;

use Models\TranslatorModel,
    Nette\Http\IRequest,
    Nette\Application\UI\Presenter;

/** @author Lubomir Andrisek */
final class ProcessForm extends ReactForm implements IProcessFormFactory {

    /** @var TranslatorModel */
    private $translatorModel;

    /** @var IProcessService */
    private $service;

    public function __construct($jsDir, IRequest $request, TranslatorModel $translatorModel) {
        parent::__construct($jsDir, $request, $translatorModel);
        $this->translatorModel = $translatorModel;
    }

    /** @return IProcessFormFactory */
    public function create() {
        return $this;
    }

    public function setService(IProcessService $service) {
        $this->service = $service;
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof Presenter and false == $this->isSignalled()) {
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
