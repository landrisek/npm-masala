<?php

namespace Masala;

use MongoDB\Client;

/** @author Lubomir Andrisek */
final class MongoMock {

    /** @var Client */
    private $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function getTestRow(string $database, string $collection, array $filters = []): array {
        $sum = $this->client->selectCollection($database, $collection)
            ->aggregate([['$group' => ['_id' => null, 'total' => ['$sum' => 1]]]])
            ->toArray();
        $sum = reset($sum)->total;
        if(null == $row = $this->client->selectCollection($database, $collection)
                        ->findOne($filters, ['skip' => rand(0, $sum - 1)])) {
            return [];
        }
        return $row->getArrayCopy();
    }

}