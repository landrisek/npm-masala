<?php

namespace App\ApiModule;

use Masala\HelpModel,
    Masala\IBuilder,
    Masala\IEditFormFactory,
    Masala\IMasalaFactory,
    Masala\IProcessService,
    Masala\IRowBuilder,
    Masala\MockModel,
    Masala\MockService,
    Nette\Application\UI\Presenter;

class DemoPresenter extends Presenter {

    /** @var HelpModel @inject */
    public $helpModel;

    /** @var MockModel @inject */
    public $mockModel;

    /** @var MockService @inject */
    public $mockService;

    /** @var IProcessService */
    public $import;

    /** @var IProcessService */
    public $export;

    /** @var IProcessService */
    public $service;

    /** @var IMasalaFactory @inject */
    public $masalaFactory;

    /** @var IEditFormFactory inject */
    public $masalaFormFactory;

    /** @var IBuilder @inject */
    public $grid;

    /** @var IRowBuilder @inject */
    public $row;

    public function startup() {
        parent::startup();
        $this->payload->message = 'Nothing to debug.';
    }

    /** action methods */
    public function actionConfig() {
        $this->payload->todo = '@todo';
        $this->sendPayload();
    }

    public function actionConcat() {
        $this->payload->todo = '@todo';
        $this->sendPayload();
    }

    public function actionExport() {
        $this->payload->todo = '@todo';
        $this->sendPayload();
    }

    public function actionFilter() {
        $this->payload->todo = '@todo';
        $this->sendPayload();
    }

    public function actionImport() {
        $this->payload->todo = '@todo';
        $this->sendPayload();
    }

    public function actionLink() {
        $this->payload->todo = '@todo';
        $this->sendPayload();
    }

    public function actionMigration() {
        $this->payload->todo = '@todo';
        $this->sendPayload();
    }

    public function actionRedraw() {
        $this->payload->todo = '@todo';
        $this->sendPayload();
    }

    public function actionNoSql() {
        $this->payload->todo = '@todo';
        $this->sendPayload();
    }

    public function actionDefault() {
        
    }

    public function actionGrid() {
        $testTable = reset($this->context->parameters['tables']);
        $this->grid->table($testTable);
        $this->row->source($testTable)
                /*->where('my_column', $id)*/
                ->check();
    }

    public function actionMock() {
        $this->mockService->getPresenters();
        $this->sendPayload();
    }
    public function actionTest() {
        $this->template->setFile(__DIR__ . '/test.latte');
    }

    /** render methods */
    public function renderDefault() {
        $this->template->setFile(__DIR__ . '/default.latte');
    }

    public function renderGrid() {
        $this->template->setFile(__DIR__ . '/grid.latte');
    }

    public function renderEdit() {
        $this->template->setFile(__DIR__ . '/edit.latte');
    }

    public function renderMock() {
        $this->template->presenters = $this->mockService->getPresenters(true);
        $this->template->setFile(__DIR__ . '/mock.latte');
    }

    /** create components methods */
    protected function createComponentMasala() {
        return $this->masalaFactory->create();
    }

    protected function createComponentMasalaForm() {
        return $this->masalaFormFactory->create()
                        ->setRow($this->row);
    }

}
