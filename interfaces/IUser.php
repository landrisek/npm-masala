<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IUser {

    public function updateUser(int $id, array $data): int;
}
