<?php

namespace Masala;

/** @author Lubomir Andrisek */
interface IEdit {

    public function after(IReactFormFactory $form): void;

    public function submit(array $primary, array $response): array;

    public function validate(array $data): array;

}
