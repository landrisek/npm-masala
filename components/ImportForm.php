<?php

namespace Masala;

use Nette\Application\IPresenter,
    Nette\ComponentModel\IComponent,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class ImportForm extends ReactForm implements IImportFormFactory {

    /** @var IProcess */
    private $facade;

    /** @var ITranslator */
    private $translatorRepository;

    public function __construct(string $css, string $js, IRequest $request, ITranslator $translatorRepository) {
        parent::__construct($css, $js, $request);
        $this->translatorRepository = $translatorRepository;
    }

    public function attached(IComponent $presenter): void {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter and false == $this->isSignalled()) {
            $this->addProgressBar('_prepare');
            $this->facade->attached($this);
            $this->addUpload('_import',
                $this->translatorRepository->translate('Drop your file here or double click to select file on disk.'),
                [], ['alt' => $this->translatorRepository->translate('image preview'),
                    'required' => $this->translatorRepository->translate('There is no file to upload.'),
                    'text' => $this->translatorRepository->translate('Uploaded file is not valid text type.')]);
            $this->addSubmit('_submit', ucfirst($label = $this->translatorRepository->translate('upload file')), ['className' => 'btn btn-success', 'onClick'=>'submit']);
            $this->addMessage('_prepare', ucfirst($this->translatorRepository->translate('Wait until upload will be finished.')), ['className' => 'btn btn-success', 'onClick' => 'prepare', 'style' => ['display' => 'none']]);
            $this->addMessage('_done', $this->translatorRepository->translate('Your file has been uploaded.'));
        }
    }

    public function create(): ReactForm {
        return $this;
    }

    public function setFacade(IProcess $facade): IImportFormFactory {
        $this->facade = $facade;
        return $this;
    }

}

interface IImportFormFactory {

    public function create(): ReactForm;
}
