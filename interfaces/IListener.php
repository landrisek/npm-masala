<?php

namespace Masala;

interface IListener {

    public function getKeys(): array;

    public function listen(array $response): array;

}
