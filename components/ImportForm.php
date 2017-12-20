<?php

namespace Masala;

use Nette\Application\UI\Presenter,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class ImportForm extends ReactForm implements IImportFormFactory {

    /** @var IProcess */
    private $service;

    /** @var ITranslator */
    private $translatorRepository;

    public function __construct($jsDir, IRequest $request, ITranslator $translatorRepository) {
        parent::__construct($jsDir, $request, $translatorRepository);
        $this->translatorRepository = $translatorRepository;
    }

    /** @return IImportFormFactory */
    public function create() {
        return $this;
    }

    public function setService(IProcess $service) {
        $this->service = $service;
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof Presenter and false == $this->isSignalled()) {
            $this->addProgressBar('_prepare');
            $this->service->attached($this);
            $this->addUpload('_file',
                $this->translatorRepository->translate('Drop your file here or double click to select file on disk.'),
                [], ['required' => $this->translatorRepository->translate('There is no file to upload.'), 'text' => $this->translatorRepository->translate('Uploaded file is not valid text type.')]);
            $this->addSubmit('_submit', ucfirst($label = $this->translatorRepository->translate('upload file')), ['className' => 'btn btn-success', 'onClick'=>'submit']);
            $this->addSubmit('_prepare', ucfirst($this->translatorRepository->translate('start upload')), ['className' => 'btn btn-success', 'onClick' => 'prepare', 'style' => ['display' => 'none']]);
            $this->addMessage('_done', $this->translatorRepository->translate('Your file has been uploaded.'));
        }
    }

}

interface IImportFormFactory {

    /** @return ImportForm */
    function create();
}
