<?php

namespace Masala;

/** @author Lubomir Andrisek */
final class Edit implements IEdit {

    public function after(IReactFormFactory $form): void { }

    public function submit(array $primary, array $response): array {
    	return $response;
    }

}
