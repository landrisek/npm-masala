<?php

namespace Masala;

use Nette\Forms\Controls\BaseControl,
    Nette\Localization\ITranslator,
    Nette\Utils\Html;

/** @author Lubomir Andrisek */
final class Handler extends BaseControl {

    /** @var ITranslator */
    private $translatorModel;

    /** @var string */
    private $parent;
    
    /** @var string */
    private $spice;

    public function __construct($label, $spice, $parent, ITranslator $translatorModel) {
        parent::__construct($label);
        $this->spice = (string) $spice;
        $this->parent = (string) $parent;
        $this->translatorModel = $translatorModel;
    }

    /** @return static */
    public function getControl() {
        $handler = Html::el('a')
                ->setText(ucfirst($this->translatorModel->translate($this->getName())))
                ->href('javascript:;');
        $data = json_encode(['id' => '#' . $this->parent, 'spice' => $this->spice, 'signal' => $this->getName()]);
        if ('delete' == $this->getName()) {
            $handler->setAttribute('data-confirm', $this->translatorModel->translate('Do you really wish to delete?'))
                    ->setAttribute('onclick', 'handleDelete(' . $data . ');')
                    ->setAttribute('class', 'btn btn-danger pull-right');
        } else {
            $handler->setAttribute('onclick', 'handleSubmit(' . $data . ');')
                    ->setAttribute('class', 'btn btn-success');
        }
        return $handler;
    }

}
