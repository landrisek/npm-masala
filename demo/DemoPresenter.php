<?php

namespace Masala\Demo;

use Masala\IBuilder,
    Masala\IMasalaFactory,
    Nette\Application\UI\Presenter,
    Nette\Localization\ITranslator,
    Nette\Security\Identity,
    Nette\Security\IIdentity;

class DemoPresenter extends Presenter {

    /** @var IBuilder @inject */
    public $grid;

    /** @var IIdentity */
    public $identity;

    /** @var IMasalaFactory @inject */
    public $masalaFactory;

    /** @var IBuilder */
    public $row;

    /** @var string */
    private $table;

    /** @var ITranslator @inject */
    public $translatorModel;

    /** @return void */
    public function startup() {
        parent::startup();
        $this->table = reset($this->context->parameters['tables']);
        $this->row = $this->grid->copy();
    }

    /** @return void */
    public function actionDefault() {
        $this->identity = new Identity(86, null, ['settings' => '[]']);
        $this->grid->table($this->table);
    }

    /** @return void */
    public function actionEdit() {
        $this->identity = new Identity(86, null, ['settings' => '[]']);
        $this->row->table($this->table);
    }

    /** @return IMasalaFactory */
    protected function createComponentMasala() {
        return $this->masalaFactory->create()
                    ->setGrid($this->grid)
                    ->setRow($this->row);
    }

}
