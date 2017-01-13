<?php

namespace App\ApiModule;

use Masala\MockModel,
    Masala\IDialogService,
    Masala\IEditFormFactory,
    Masala\IMasalaFactory,
    Masala\IProcessService,
    Masala\MockService,
    Masala\RowBuilder,
    Nette\Application\UI\Presenter;

class DemoPresenter extends Presenter {

    /** @var MockModel @inject */
    public $mockModel;

    /** @var MockService @inject */
    public $mockService;

    /** @var IProcessService */
    public $import;

    /** @var IProcessService */
    public $export;

    /** @var IDialogService */
    public $dialog;

    /** @var IProcessService */
    public $service;

    /** @var IMasalaFactory @inject */
    public $masalaFactory;

    /** @var IEditFormFactory inject */
    public $masalaFormFactory;

    /** @var RowBuilder */
    public $setting;

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
        $this->grid->table('my_table');
    }

    public function actionEdit($id = 1) {
        if (false == $this->setting->source('my_table')
                        ->where('my_column', $id)
                        ->check()) {
            $this->flashMessage('Item with ' . $id . ' does not exist.');
            $this->redirect('Demo:default');
        }
    }

    public function actionMock() {
        $this->mockService->getPresenters();
        $this->sendPayload();
    }

    public function actionTest() {
        $this->sendPayload();
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
                        ->setSetting($this->setting);
    }

}
