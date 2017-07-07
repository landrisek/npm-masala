<?php

namespace Masala;

interface IEdit {

    /** @return IReactFormFactory */
    function after(IReactFormFactory $form, IRow $row);

    /** @return array */
    function submit(array $response);

}
