<?php

namespace App;

use Masala\IBuilder,
    Masala\IEditFormFactory,
    Masala\IMasalaFactory,
    Masala\IRow,
    Masala\EmptyRow,
    Nette\Application\UI\Presenter,
    Nette\Localization\ITranslator;

class DemoPresenter extends Presenter {

    /** @var IMasalaFactory @inject */
    public $masalaFactory;

    /** @var IEditFormFactory inject */
    public $editFormFactory;

    /** @var IBuilder @inject */
    public $grid;

    /** @var IRow @inject */
    public $row;

    /** @var string */
    private $table;

    /** @var ITranslator @inject */
    public $translatorRepository;

    /** @return void */
    public function startup() {
        parent::startup();
        $this->table = reset($this->context->parameters['tables']);
    }

    /** @return void */
    public function actionDefault() {
        $this->grid->table($this->table);
    }

    /** @return void */
    public function actionEdit() {
        if ($this->row->table($this->test)
                ->check() instanceof EmptyRow) {
            $this->flashMessage($this->translatorRepository->translate('Choosen item does not exist.'));
            $this->redirect('App:Demo:default');
        }
    }

    /** @return IMasalaFactory */
    protected function createComponentMasala() {
        return $this->masalaFactory->create();
    }

    /** @return IEditFormFactory */
    protected function createComponentEditForm() {
        return $this->editFormFactory->create()
                        ->setRow($this->row);
    }

}
