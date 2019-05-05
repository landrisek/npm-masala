<?php

namespace Masala\Examples;

use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Control;

/** @author Lubomir Andrisek */
final class MyComponent extends Control implements IMyComponentFactory {

    public function props(): array {
        return ['myComponentId' => ['label' => 'myLabel']];
    }

    public function create(): MyComponent {
        return $this;
    }

    public function handleState(): void {
        $state = json_decode(file_get_contents('php://input'), false);
        $state->myComponentId->value = 'myValue';
        $this->presenter->sendResponse(new JsonResponse($state));
    }

}

interface IMyComponentFactory {

    public function create(): MyComponent;
}
