<?php

namespace Masala;

interface IEditService {

    /** @return IReactFormFactory */
    function add(IReactFormFactory $form, $row);

    /** @return IReactFormFactory */
    function afterAttached(IReactFormFactory $form);

    /** @return IReactFormFactory */
    function beforeAttached(IReactFormFactory $form);

    /** @return array */
    function submit(array $response);

}
