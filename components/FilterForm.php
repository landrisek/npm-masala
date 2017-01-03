<?php

namespace Masala;

use Models\TranslatorModel,
    Nette\Forms\Container,
    Nette\Application\UI\Form,
    Nette\Application\UI\Presenter;

/** @author Lubomir Andrisek */
final class FilterForm extends Form implements IFilterFormFactory {

    /** @var IBuilder */
    private $grid;

    /** @var TranslatorModel */
    private $translatorModel;

    public function __construct(TranslatorModel $translatorModel) {
        parent::__construct(null, null);
        $this->translatorModel = $translatorModel;
    }

    /** @return IFilterFormFactory */
    public function create() {
        return $this;
    }

    /** setters */
    public function setGrid(IBuilder $grid) {
        $this->grid = $grid;
        return $this;
    }

    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof Presenter) {
            $this->setMethod('post');
            $this['filter'] = new Container;
            $defaults = $this->grid->getDefaults();
            foreach ($this->grid->getColumns() as $name => $annotation) {
                /** filter */
                if (true == $this->grid->getAnnotation($name, ['unfilter', 'hidden'])) {

                } elseif (true == $this->grid->getAnnotation($name, 'range') or is_array($this->grid->getRange($name))) {
                    $this['filter']->addRange($name, ucfirst($this->translatorModel->translate($name)), $defaults[$name]);
                } elseif (is_array($defaults[$name]) and ! empty($defaults[$name])) {
                    $this['filter']->addSelect($name, ucfirst($this->translatorModel->translate($name)), $defaults[$name])
                            ->setAttribute('class', 'form-control')
                            ->setAttribute('onchange', 'handleFilter()')
                            ->setPrompt($this->translatorModel->translate('--unchosen--'));
                } else {
                    $this['filter']->addText($name, ucfirst($this->translatorModel->translate($name)))->setAttribute('class', 'form-control');
                }
                /** default values */
                if (true == $this->grid->getAnnotation($name, ['unfilter', 'hidden'])) {
                } elseif (true == $this->grid->getAnnotation($name, 'fetch') and ! preg_match('/\(/', $annotation) and is_array($default = $defaults[$name])) {
                    $default = array_shift($default);
                    $default = is_object($default) ? $default->__toString() : $default;
                    $this['filter'][$name]->setDefaultValue($default);
                } elseif (is_array ($this->grid->getFilter($this->grid->getColumn($name)))) {
                } elseif (is_array ($defaults[$name]) and isset($defaults[$name][$this->grid->getFilter($this->grid->getColumn($name))])) {
                    $this['filter'][$name]->setDefaultValue($this->grid->getFilter($this->grid->getColumn($name)));
                } elseif (is_array($defaults[$name]) and false == $this->grid->getAnnotation($name, 'range')) {
                    $this['filter'][$name]->setPrompt('-- ' . $this->translatorModel->translate('choose') . ' ' . $this->translatorModel->translate($name) . ' --');
                } elseif (isset($defaults[$name]) and false == $this->grid->getAnnotation($name, 'range')) {
                    $this['filter'][$name]->setDefaultValue($defaults[$name]);
                }
            }
        }
    }

    protected function createComponentRange() {
        return $this->rangeFactory->create();
    }

}

interface IFilterFormFactory {

    /** @return FilterForm */
    function create();
}
