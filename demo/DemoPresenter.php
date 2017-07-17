<?php

namespace App;

use Masala\IBuilder,
    Masala\IEditFormFactory,
    Masala\IMasalaFactory,
    Masala\IProcess,
    Masala\IRow,
    Nette\Application\UI\Presenter;

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

    public function startup() {
        parent::startup();
        $this->table = reset($this->context->parameters['tables']);
    }

    public function actionDefault() {
        $this->grid->table($this->table);
    }

    public function actionEdit() {
        $this->row->table($this->test)
                ->check();
    }

    /** @return IMasalaFactor */
    protected function createComponentMasala() {
        return $this->masalaFactory->create();
    }

    protected function createComponentEditForm() {
        return $this->editFormFactory->create()
                        ->setRow($this->row);
    }

}
