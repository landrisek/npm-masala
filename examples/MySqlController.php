<?php

namespace Masala\Examples;

use Masala\SqlController;
use Nette\Application\Responses\JsonResponse;

/** @author Lubomir Andrisek */
final class MySqlController extends SqlController {

    public function actionState(): void {
        $this->table('myTable')
            ->select('myColumn', 'myAlias')
            ->where('myColumn', 'MyValue')
            ->state();
        $this->state->Rows = new stdClass();
        foreach($this->database->query($this->query, ...$this->arguments)->fetchAll() as $key => $row) {
             /** ... apilication logic */
             $this->state->Rows->$key = $row;
        }
        $this->state->myValue = 'myValue';
        $this->presenter->sendResponse(new JsonResponse($this->state));
    }

    public function actionPage(string $key): void {
        $this->build($key);
        parent::actionPage($key);
    }

    private function build(string $type): void {
        $this->table('myTable')
            ->select('myColumn', 'myLabel', 'myAlias')
            ->where('myColumn', 'myValue')
            ->state();
    }

    public function renderDefault(): void {
        $this->mount('filter data', 'myAction');
        $this->props['menu'] = ['bookmark1' => ucfirst($this->translatorRepository->translate('bookmark1')),
            'bookmark2' => ucfirst($this->translatorRepository->translate('bookmark2')),
            'bookmark3' => ucfirst($this->translatorRepository->translate('bookmark3'))];
        $this->template->props = json_encode($this->props);
        $this->unmount($this->id);
    }

    public function startup(): void {
        parent::startup();
    }
}
