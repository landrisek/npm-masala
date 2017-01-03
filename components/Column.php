<?php

namespace Masala;

use Nette\Object;

final class Column extends Object {

    /** @var string */
    public $name;

    /** @var string */
    public $label;

    /** @var IBuilder */
    protected $grid;

    public function __construct($name, $label, IBuilder $grid) {
        $this->name = $name;
        $this->label = $label;
        $this->grid = $grid;
    }

    public function getNewState() {
        if ($this->isAsc()) {
            return 'DESC';
        } elseif ($this->isDesc()) {
            return null;
        } else {
            return 'ASC';
        }
    }

    public function isAsc() {
        return (0 < preg_match('/ASC/', $this->grid->getSort()) and $this->name == $this->grid->getOrder());
    }

    public function isDesc() {
        return (0 < preg_match('/DESC/', $this->grid->getSort()) and $this->name == $this->grid->getOrder());
    }

}
