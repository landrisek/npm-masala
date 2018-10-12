<?php

namespace Masala;

interface IUpdate {

    public function update(string $key, array $data): array;

}
