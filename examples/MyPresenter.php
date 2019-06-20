<?php

namespace Masala\Examples;

use Nette\Application\UI\Presenter;

/** @author Lubomir Anrisek */
final class MyPresenter extends Presenter {

    public function beforeRender(): void {
        parent::beforeRender();
        $this->template->id = $this->getName();
        $this->template->src = self::ASSETS . $this->template->id;
    }

    private function injectMyComponent(IMyComponentFactory $myComponentFactory): void {
        $this->addComponent($myComponentFactory->create()->build(), 'myComponentFactory');
    }

    private function injectMyOtherComponent(IMyComponentFactory $myOtherComponentFactory): void {
        $this->addComponent($myOtherComponentFactory->create()->otherBuild(), 'myOtherComponentFactory');
    }

    public function renderDefault(): void {
        $this->template->props = json_encode(['menu1' => ucfirst($this->translatorModel->translate('menu1')),
            'menu1' => ucfirst($this->translatorModel->translate('menu2')),
            'myData' => $this['myComponentFactory']->props(),
            'myOtherData' => $this['myOtherComponentFactory']->props()]);
    }

}
