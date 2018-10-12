<?php

namespace Masala;

interface IUser {

    public function updateUser(int $id, array $data): int;
}
