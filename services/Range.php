<?php

namespace Masala;

use Latte\Engine,
    Nette\Application\UI\Form,
    Nette\Bridges\ApplicationLatte\Template,
    Nette\Forms\Controls\BaseControl,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
class Range extends BaseControl implements IRangeFactory {

    /** @var ITranslator */
    private $translator;

    /** @var Array */
    private $defaults;

    /** @var string */
    protected $label;

    /** @var string */
    protected $control;

    /** @return IRangeFactory */
    public function create() {
        return $this;
    }

    public function __construct($label = null, Array $defaults = [], ITranslator $translator) {
        parent::__construct($label);
        $this->defaults = $defaults;
        $this->translator = $translator;
    }

    public function attached($form) {
        parent::attached($form);
        if ($form instanceof Form) {
            $this->control = $form->getParent()->getName();
        }
    }

    /** getters */
    public function getControl() {
        if ((bool) strpbrk($this->defaults['>'], 1234567890) and
                strtotime($this->defaults['>']) and
                (bool) strpbrk($this->defaults['<'], 1234567890) and
                strtotime($this->defaults['<'])) {
            return $this->render('date');
        } elseif(preg_match('/\./', $this->defaults['>']) or preg_match('/\./', $this->defaults['<'])) {
            return $this->render('float');
        } else {
            return $this->render('integer');
        }
    }

    public function getValue() {
        return $this->defaults;
    }

    private function render($file) {
        $latte = new Engine();
        $template = new Template($latte);
        $template->id = $this->getName();
        $template->min = $this->defaults['min'];
        $template->max = $this->defaults['max'];
        $template->from = $this->defaults['>'];
        $template->to = $this->defaults['<'];
        $template->control = $this->control;
        $template->setFile(__DIR__ . '/../templates/' . $file . '.latte');
        $template->setTranslator($this->translator);
        return $template->__toString();
    }

}

interface IRangeFactory {

    /** @return Range */
    function create();
}
