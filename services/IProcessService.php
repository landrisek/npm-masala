<?php

namespace Masala;

use Nette\Database\Table\ActiveRow,
    Nette\Application\UI\Presenter;

interface IProcessService {

    function getSetting();
    
    function setSetting(ActiveRow $setting);
    
    function prepare(IMasalaFactory $masala);

    /** @return String */
    function run(Array $row, Array $rows, IMasalaFactory $masala);

    /** @return Bool */
    function done(Array $step, Presenter $presenter);

    /** @return string */
    function message(IMasalaFactory $masala);
}
