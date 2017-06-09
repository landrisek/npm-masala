<?php

namespace Masala;

use Models\TranslatorModel,
    Nette\Http\IRequest,
    Nette\Application\UI\Presenter;

/** @author Lubomir Andrisek */
final class ImportForm extends ReactForm implements IImportFormFactory {

    /** @var TranslatorModel */
    private $translatorModel;

    /** @var IProcessService */
    private $service;

    public function __construct($jsDir, IRequest $request, TranslatorModel $translatorModel) {
        parent::__construct($jsDir, $request, $translatorModel);
        $this->translatorModel = $translatorModel;
    }

    /** @return IImportFormFactory */
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
            $this->addUpload('file',
                $this->translatorModel->translate('Drop your file here or double click to select file on disk.'),
                [], ['required' => $this->translatorModel->translate('There is no file to upload.'),
                    'type' => 'text/csv',
                    'text/csv' => $this->translatorModel->translate('Uploaded file is not valid csv type.')]);
            $this->addSubmit('save', ucfirst($label = $this->translatorModel->translate('upload file')),
                    ['class' => 'btn btn-success',
                    'onClick'=>'submit']);
            $this->addSubmit('prepare', ucfirst($this->translatorModel->translate('start upload')),
                ['class' => 'btn btn-success',
                    'onClick' => 'prepare',
                    'style' => ['display' => 'none']]);
            $this->addMessage('done', $this->translatorModel->translate('Your file has been uploaded.'));
        }
    }

}

interface IImportFormFactory {

    /** @return ImportForm */
    function create();
}
