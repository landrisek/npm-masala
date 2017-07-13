<?php

namespace Masala;

interface IUser {

    /** @return int */
    function updateUser($id, array $data);

}
