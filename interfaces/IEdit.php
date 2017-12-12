<?php

namespace Masala;

interface IEdit {

    /** @return IReactFormFactory */
    public function after(IReactFormFactory $form, IRow $row);

    /** @return array */
    public function submit(array $primary, array $response);

}
