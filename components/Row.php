<?php

namespace Masala;

use Latte\Engine,
    Nette\Application\UI\Presenter,
    Nette\Bridges\ApplicationLatte\UIMacros,
    Nette\Bridges\ApplicationLatte\Template,
    Nette\Bridges\FormsLatte\FormMacros,
    Nette\Application\UI\Control,
    Nette\Database\Table\ActiveRow,
    Nette\Localization\ITranslator;

final class Row extends Control {

    /** @var Presenter */
    private $presenter;

    /** @var ITranslator */
    private $translatorModel;

    /** @var Array */
    private $columns;

    /** @var int */
    private $page;

    /** @var IBuilder */
    private $grid;

    /** @var ActiveRow */
    private $setting;

    /** @var string */
    private $actions;

    /** @var string */
    private $control;

    /** @var string */
    private $primary;

    public function __construct($actions, Array $columns, $control, $page, $primary, IBuilder $grid, Presenter $presenter, ITranslator $translatorModel) {
        $this->actions = (string) $actions;
        $this->columns = $columns;
        $this->control = (string) $control;
        $this->page = intval($page);
        $this->grid = $grid;
        $this->setting = $grid->loadRow($primary);
        $this->primary = $primary;
        $this->presenter = $presenter;
        $this->translatorModel = $translatorModel;
    }

    /** render methods */
    public function getTemplate() {
        $latte = new Engine();
        $latte->onCompile[] = function($latte) {
            FormMacros::install($latte->getCompiler());
            UIMacros::install($latte->getCompiler());
        };
        $latte->addProvider('uiPresenter', $this->presenter);
        $latte->addProvider('uiControl', $this);
        $template = new Template($latte);
        $template->setFile(__DIR__ . '/../templates/row.latte');
        $template->actions = $this->actions;
        $template->columns = $this->columns;
        $template->grid = $this->grid;
        $template->hash = $this->grid->getHash();
        $template->primary = $this->primary;
        $template->row = $this->setting;
        $template->page = $this->page;
        $template->control = $this->control;
        $template->presenter = $this->presenter;
        $template->setTranslator($this->translatorModel);
        return $template;
     }

     public function string() {
        return $this->getTemplate()->__toString();
     }

     public function render() {
        $this->getTemplate()->render();
    }

}
