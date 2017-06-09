<?php

namespace App;

use Masala\HelpModel,
    Masala\IBuilder,
    Masala\IEditFormFactory,
    Masala\IMasalaFactory,
    Masala\IProcessService,
    Masala\IRowBuilder,
    Nette\Application\UI\Presenter;

class DemoPresenter extends Presenter {

    /** @var HelpModel @inject */
    public $helpModel;

    /** @var IProcessService */
    public $import;

    /** @var IProcessService */
    public $export;

    /** @var IProcessService */
    public $service;

    /** @var IMasalaFactory @inject */
    public $masalaFactory;

    /** @var IEditFormFactory inject */
    public $editFormFactory;

    /** @var IBuilder @inject */
    public $grid;

    /** @var IRowBuilder @inject */
    public $row;

    public function actionDefault() {
        $testTable = reset($this->context->parameters['tables']);
        $this->grid->table($testTable);
        $this->row->table($testTable)
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
