<?php

namespace Masala;

use Nette\Application\UI\Form;

interface IEditFormService {

    /** @return Form */
    function afterAttached(Form $form);

    /** @return Form */
    function afterSucceeded(Form $form);
    
    /** @return Array */
    function beforeAttached(Form $form);

    /** @return Array */
    function beforeSucceeded(Form $form);
    
    function add(Form $form, $row);
}
