<?php

namespace Masala;

use Nette\Application\UI\Form,
    Nette\Utils\ArrayHash;

interface IEditFormService {

    /** @return Form */
    function afterAttached(Form $form);

    /** @return string */
    function afterSucceeded(Form $form);

    /** @return ArrayHash */
    function beforeAttached(Form $form);

    /** @return ArrayHash */
    function beforeSucceeded(Form $form);

    function add(Form $form, $row);
}
