<?php

namespace Masala\Examples;

use Masala\IBuilderFactory,
    Nette\Application\UI\Presenter;

/** @author Lubomir Anrisek */
final class MyPresenter extends Presenter {

    /** @var IMyComponentFactory @inject */
    private $myComponentFactory;

    protected function createComponentMyComponent(): IBuilderFactory {
        return $this->myComponentFactory->create();
    }

}
