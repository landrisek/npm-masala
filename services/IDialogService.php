<?php

namespace Masala;

interface IDialogService {

    function submit(Array $values);

    /** @return Array */
    function getComponents();
}
