<?php

namespace Masala;

final class Edit implements IEdit {

    /** @return IReactFormFactory */
    public function after(IReactFormFactory $form) {
    	return $form;
    }

    /** @return array */
    public function submit(array $primary, array $response) {
    	return $response;
    }

}
