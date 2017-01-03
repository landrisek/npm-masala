<?php

namespace Masala;

use Latte\Engine,
    Models\TranslatorModel,
    Nette\Bridges\ApplicationLatte\Template,
    Nette\Bridges\FormsLatte\FormMacros,
    Nette\Application\UI\Form,
    Nette\Application\UI\Presenter;

/** @author Lubomir Andrisek */
class ImportForm extends Form implements IImportFormFactory {

    /** @var Array */
    private $components;

    /** @var Array */
    private $dividers = [',', ';', '"'];

    /** @var TranslatorModel */
    private $translatorModel;

    /** @var IEditFormService */
    private $service;

    public function __construct(TranslatorModel $translatorModel) {
        parent::__construct(null, null);
        //$this->service = $service;
        $this->translatorModel = $translatorModel;
    }

    /** @return IImportFormFactory */
    public function create() {
        return $this;
    }

    public function setService($service) {
        $this->service = $service;
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof Presenter) {
            $this->setMethod('post');
            ($this->service instanceof IEditFormService) ? $this->service->beforeAttached($this) : null;
            $this->addUpload('file', ucfirst($this->translatorModel->translate('file')))
                    ->setRequired($this->translatorModel->translate('Choose file for uploading first.'))
                    ->setAttribute('class', 'btn btn-warning btn-outline');
            $this->addSubmit('save', ucfirst($this->translatorModel->translate('upload file')))
                    ->setAttribute('class', 'btn btn-success');
            $this->onSubmit[] = [$this, 'succeeded'];
        }
    }

    public function succeeded(Form $form) {
        $values = ($this->service instanceof IEditFormService) ? $this->service->beforeSucceeded($form) : $form->getValues();
        $parent = $this->getParent()->getName();
        $folder = $this->getPresenter()->getContext()->parameters['tempDir'] . '/' . $parent . '/' . str_replace(':', '', $this->getPresenter()->getName() . $this->getPresenter()->getAction()) . '/';
        $file = strtotime('now');
        !file_exists($folder) ? mkdir($folder, 0755, true) : null;
        $values->file->move($folder . $file . '.csv');
        $divider = $this->getDivider($values->file->getTemporaryFile());
        $this->getPresenter()->redirect('this', ['do' => $parent . '-csv', $parent . '-file' => $file, $parent . '-divider' => $divider]);
    }

    private function getDivider($file) {
        $handle = fopen($file, 'r');
        $dividers = [];
        foreach ($this->dividers as $divider) {
            $line = fgetcsv($handle, 10000, $divider);
            $dividers[count($line)] = $divider;
        }
        fclose($handle);
        ksort($dividers);
        $divider = array_reverse($dividers);
        return array_shift($divider);
    }

    /** render methods */
    public function render(...$args) {
        $latte = new Engine();
        $latte->onCompile[] = function($latte) {
            FormMacros::install($latte->getCompiler());
        };
        $template = new Template($latte);
        $template->setFile(__DIR__ . '/../templates/import.latte');
        $template->form = $this;
        $template->basePath = $this->getPresenter()->template->basePath;
        $template->setTranslator($this->translatorModel);
        $template->components = $this->components;
        $template->feed = $this->service->getSetting()->feed;
        $template->render();
    }

}

interface IImportFormFactory {

    /** @return ImportForm */
    function create();
}
