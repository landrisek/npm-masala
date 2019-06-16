<?php

namespace Grids;

use Masala\IBuilderFactory;
use Masala\MongoBuilder;
use MongoDB\Client;
use Nette\Application\Responses\JsonResponse;
use Nette\Localization\ITranslator;
use stdClass;

/** @author Lubomir Andrisek */
final class MyMongoGrid extends MongoBuilder implements IBuilderFactory, IMyMongoGridFactory {

    /** @var string */
    private $source;

    public function __construct(string $database, string $source, Client $client, ITranslator $translatorRepository) {
        parent::__construct($database, $client, $translatorRepository);
        $this->source = $source;
    }

    public function attached(\Nette\ComponentModel\IComponent $presenter): void {
        $this->table('myTable')
             ->select('myColumn', 'myLabel', 'myAlias')
             ->where('myColumn', 'myValue')
             ->file('myFile')
             ->prop('myColumn', ['label' => 'myLabel', 'data' => ['key1' => 'value1']])
             ->prop('myMultiSelect', ['cancel' => $this->translatorRepository->translate('cancel'),
                                      'label' => $this->translatorRepository->translate('myLabel'),
                                      'data' => ['_0' => 'value1', '_1' => 'value2'],
                                      'placeholder' => $this->translatorRepository->translate('choose my value')])
             ->fetch();
    }

    public function create(): MyMongoGrid {
        return $this;
    }

    protected function file(): string {
        return 'MyFile';
    }

    public function handleEdit(): void {
        $this->state();
        /** update logic */
        $this->presenter->sendResponse(new JsonResponse($this->state));
    }

    public function handleProcess(): void {
        $this->state();
        /** process logic **/
        $this->state->Paginator->Current++;
        $this->presenter->sendResponse(new JsonResponse($this->state));
    }
    public function handleState(): void {
        $this->state();
        $this->state->Rows = new stdClass();
        foreach($this->client->selectCollection($this->database, $this->collection)->find($this->arguments, $this->options)->toArray() as $key => $row) {
            $this->state->Rows->$key = $row;
            /** render logic */
        }
        $this->presenter->sendResponse(new JsonResponse($this->state));
    }

    public function props(): array {
        return parent::props();
    }

}

interface IMyMongoGridFactory {

    public function create(): MyMongoGrid;
}
