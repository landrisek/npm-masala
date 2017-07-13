<?php

namespace Masala;

use Nette\Application\UI\Control,
    Nette\Application\UI\ISignalReceiver,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class ContentForm extends Control implements IContentFormFactory {

    /** @var int */
    private $id;

    /** @var string */
    private $jsDir;

    /** @var IRequest */
    private $request;

    /** @var IRow */
    private $row;

    /** @var ITranslator */
    private $translatorModel;

    /** @var WriteModel */
    private $writeModel;

    public function __construct($jsDir, IRequest $request, ITranslator $translatorModel, WriteModel $writeModel) {
        $this->jsDir = $jsDir;
        $this->writeModel = $writeModel;
        $this->request = $request;
        $this->translatorModel = $translatorModel;
    }

    /** @return IContentFormFactory */
    public function create() {
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if($presenter instanceof ISignalReceiver) {
            $this->id = $this->request->getQuery('id');
        }
    }

    public function handleSubmit() {
        $this->writeModel->updateWrite($this->id, ['content' => $this->request->getPost('content')]);
        die();
    }

    public function render(...$args) {
        $this->template->component = $this->getName();
        $this->template->data =  trim($this->row->check()->content);
        $this->template->links =  json_encode(['submit' => $this->link('submit')]);
        $this->template->js = $this->getPresenter()->template->basePath . '/' . $this->jsDir;
        $this->template->setFile(__DIR__ . '/../templates/content.latte');
        $this->template->render();
    }

    /** @return IContentFormFactory */
    public function setRow(IRow $row) {
        $this->row = $row;
        return $this;
    }

}

interface IContentFormFactory {

    /** @return ContentForm */
    function create();
}
