<?php

namespace App;

use Masala\IBuilder,
    Masala\IMasalaFactory,
    Nette\Application\UI\Presenter,
    Nette\Localization\ITranslator,
    Nette\Security\Identity,
    Nette\Security\IIdentity;

/** @author Lubomir Andrisek */
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
    public $translatorRepository;

    public function startup() {
        parent::startup();
        $this->table = reset($this->context->parameters['tables']);
        $this->row = $this->grid->copy();
    }

    public function actionDefault(): void {
        $this->identity = new Identity(86, null, ['settings' => '[]']);
        $this->grid->table($this->table);
    }

    public function actionEdit(): void {
        $this->identity = new Identity(86, null, ['settings' => '[]']);
        $this->row->table($this->table);
    }

    protected function createComponentMasala(): IMasalaFactory {
        return $this->masalaFactory->create()
                    ->setGrid($this->grid)
                    ->setRow($this->row);
    }

}
